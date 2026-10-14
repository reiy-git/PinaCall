<?php

namespace Tests\Feature;

use App\Mail\StudentProvisioned;
use App\Models\ApiAccessToken;
use App\Models\EnrolleeApplication;
use App\Models\ProvisioningEmail;
use App\Models\User;
use App\Services\ProvisioningMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnrolleeApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function apply(): EnrolleeApplication
    {
        Storage::fake('local');
        $this->post('/api/enrollees/apply', [
            'name' => 'Kim Applicant', 'email' => 'KIM@example.com', 'contact_number' => '09123456789',
            'program' => 'BS Computer Science', 'student_type' => 'new', 'year_level' => 1,
            'educational_background' => 'Senior high school graduate',
            'previous_school' => 'Example Secondary School',
            'documents' => [UploadedFile::fake()->create('credentials.pdf', 20, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('status', 'PENDING');

        return EnrolleeApplication::firstOrFail();
    }

    private function token(User $user): string
    {
        $token = Str::random(80);
        ApiAccessToken::create(['user_id' => $user->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addHours(8)]);

        return $token;
    }

    private function reviewer(string $role = 'registrar'): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->withHeader('Authorization', 'Bearer '.$this->token($user));

        return $user;
    }

    public function test_guest_application_does_not_create_credentials_and_registration_is_closed(): void
    {
        $a = $this->apply();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('api_access_tokens', 0);
        $this->assertSame('kim@example.com', $a->email);
        $this->assertSame('Example Secondary School', $a->previous_school);
        $this->assertSame('BS Computer Science', $a->program);
        $this->assertSame('new', $a->student_type);
        $this->assertSame(1, $a->year_level);
        $this->assertSame('09123456789', $a->contact_number);
        $this->assertSame('Senior high school graduate', $a->educational_background);
        Storage::disk('local')->assertExists($a->documents[0]['path']);
        $this->postJson('/api/auth/register', [])->assertNotFound();
        $this->getJson('/api/admin/enrollees')->assertUnauthorized();
    }

    public function test_approval_is_idempotent_and_password_gate_is_enforced_on_every_api(): void
    {
        Mail::fake();
        $a = $this->apply();
        $this->reviewer();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", ['remarks' => 'Verified'])->assertOk()->assertJsonPath('application.status', 'APPROVED');
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertOk();
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('provisioning_emails', 1);
        $this->assertDatabaseCount('student_applications', 1);
        $user = User::where('email', 'kim@example.com')->firstOrFail();
        $outbox = ProvisioningEmail::firstOrFail();
        $password = $outbox->temporary_password;
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotSame($password, $outbox->getRawOriginal('temporary_password'));
        app(ProvisioningMailer::class)->send($outbox->id);
        Mail::assertSent(StudentProvisioned::class, 1);
        $this->assertNull($outbox->fresh()->temporary_password);
        app(ProvisioningMailer::class)->send($outbox->id);
        Mail::assertSent(StudentProvisioned::class, 1);
        $login = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $password])->assertOk()->assertJsonPath('user.must_change_password', true);
        $this->withHeader('Authorization', 'Bearer '.$login->json('access_token'));
        $this->getJson('/api/courses')->assertForbidden()->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
        $this->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/first-login-reset', ['current_password' => 'wrong', 'password' => 'New-Secure-Password-2026!', 'password_confirmation' => 'New-Secure-Password-2026!'])->assertUnprocessable();
        $this->postJson('/api/auth/first-login-reset', ['current_password' => $password, 'password' => 'New-Secure-Password-2026!', 'password_confirmation' => 'New-Secure-Password-2026!'])->assertOk();
        $this->getJson('/api/courses')->assertUnauthorized();
        $this->assertFalse($user->fresh()->must_change_password);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $password])->assertUnauthorized();
        $login = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'New-Secure-Password-2026!'])->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$login->json('access_token'));
        $this->getJson('/api/courses')->assertOk();
    }

    public function test_approved_guest_cannot_repeat_initial_admission_but_can_use_continuing_services(): void
    {
        $a = $this->apply();
        $this->reviewer();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertOk();
        $user = User::findOrFail($a->fresh()->user_id);
        $this->assertDatabaseHas('student_applications', ['user_id' => $user->id, 'previous_school' => 'Example Secondary School']);
        $this->assertDatabaseHas('clearances', ['student_id' => $user->student->id, 'status' => 'pending', 'cashier_status' => false]);
        $this->assertDatabaseCount('section_enrollments', 0);
        $user->update(['must_change_password' => false]);
        $this->withHeader('Authorization', 'Bearer '.$this->token($user));
        $this->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('user.approved_admission.reference', $a->reference)
            ->assertJsonMissingPath('user.approved_admission.documents');
        $payload = ['student_type' => 'new', 'target_program' => 'BS Computer Science', 'year_level' => 1, 'educational_background' => 'Graduate'];
        foreach (['new', 'transferee'] as $category) {
            $payload['student_type'] = $category;
            $this->postJson('/api/applications', $payload)->assertConflict();
        }
        $this->assertDatabaseCount('student_applications', 1);
        $this->assertSame('active', $user->student->fresh()->status);
        $this->getJson('/api/grades')->assertOk();
        $this->getJson('/api/clearances')->assertOk();
        $this->getJson('/api/sections')->assertOk();
        $this->getJson('/api/documents')->assertOk();
        foreach (['old', 'irregular', 'shiftee', 'returnee'] as $category) {
            $payload['student_type'] = $category;
            $this->postJson('/api/applications', $payload)->assertCreated()->assertJsonPath('application.student_type', $category);
            \App\Models\StudentApplication::where('user_id', $user->id)->where('status', 'pending')->update(['status' => 'approved']);
        }
    }

    public function test_guest_fields_and_document_limits_are_validated_without_writes(): void
    {
        // Exercise many invalid payloads in one test without exhausting the live limiter.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Storage::fake('local');
        $valid = ['name' => 'Applicant', 'email' => 'applicant@example.com', 'contact_number' => '123', 'program' => 'BS Computer Science', 'student_type' => 'new', 'year_level' => 1, 'educational_background' => 'Graduate'];
        foreach ([['previous_school', str_repeat('x', 256)], ['previous_school', ['invalid']], ['name', ''], ['email', 'invalid'], ['contact_number', str_repeat('1', 31)], ['year_level', 7], ['year_level', 1.5], ['student_type', 'unsupported'], ['educational_background', str_repeat('x', 5001)]] as [$field, $value]) {
            $payload = array_merge($valid, [$field => $value, 'documents' => [UploadedFile::fake()->create('record.pdf', 20, 'application/pdf')]]);
            $this->post('/api/enrollees/apply', $payload, ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        foreach ([[], array_map(fn () => UploadedFile::fake()->create('record.pdf', 20, 'application/pdf'), range(1, 6)), [UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')]] as $documents) {
            $this->post('/api/enrollees/apply', array_merge($valid, ['documents' => $documents]), ['Accept' => 'application/json'])->assertUnprocessable();
        }
        $this->assertDatabaseCount('enrollee_applications', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
        $this->post('/api/enrollees/apply', array_merge($valid, ['documents' => [UploadedFile::fake()->create('record.pdf', 20, 'application/pdf')]]), ['Accept' => 'application/json'])->assertCreated();
        $this->assertNull(EnrolleeApplication::firstOrFail()->previous_school);
    }

    public function test_public_submission_remains_throttled(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/enrollees/apply', [])->assertUnprocessable();
        }
        $this->postJson('/api/enrollees/apply', [])->assertTooManyRequests();
        $this->assertDatabaseCount('enrollee_applications', 0);
    }

    public function test_public_options_use_configured_programs_and_document_guidance(): void
    {
        config(['enrollment.programs' => ['Configured program'], 'enrollment.document_guidance' => 'Ask the Registrar for the current list.']);
        $this->getJson('/api/enrollees/options')->assertOk()
            ->assertExactJson(['programs' => ['Configured program'], 'document_guidance' => 'Ask the Registrar for the current list.']);
    }

    public function test_rejection_and_role_checks_never_provision_an_account(): void
    {
        $a = $this->apply();
        $this->reviewer('student');
        $this->getJson('/api/admin/enrollees')->assertForbidden();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertForbidden();
        $this->getJson("/api/admin/enrollees/{$a->id}/documents/0")->assertForbidden();
        $this->reviewer('admin');
        $this->getJson('/api/admin/enrollees')->assertOk()->assertJsonPath('data.0.documents.0.index', 0)->assertJsonMissingPath('data.0.documents.0.path');
        $this->get("/api/admin/enrollees/{$a->id}/documents/0")->assertOk();
        $this->postJson("/api/admin/enrollees/{$a->id}/reject", ['remarks' => 'Incomplete'])->assertOk();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertConflict();
        $this->assertDatabaseMissing('users', ['email' => 'kim@example.com']);
        $this->assertDatabaseCount('provisioning_emails', 0);
    }

    public function test_existing_email_conflict_rolls_back_the_entire_approval(): void
    {
        $a = $this->apply();
        $this->reviewer();
        User::factory()->create(['email' => 'kim@example.com']);
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertConflict();
        $this->assertSame('PENDING', $a->fresh()->status);
        $this->assertNull($a->fresh()->user_id);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('provisioning_emails', 0);
    }

    public function test_failed_mail_is_retryable_and_expired_credentials_can_be_reissued(): void
    {
        $a = $this->apply();
        $this->reviewer();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertOk();
        $outbox = ProvisioningEmail::firstOrFail();
        $oldPassword = $outbox->temporary_password;
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));
        app(ProvisioningMailer::class)->send($outbox->id);
        $this->assertSame(1, $outbox->fresh()->attempts);
        $this->assertNull($outbox->fresh()->sent_at);
        $user = $outbox->user;
        $user->update(['temporary_password_expires_at' => now()->subMinute()]);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $oldPassword])->assertForbidden();
        $this->postJson("/api/admin/enrollees/{$a->id}/resend", [])->assertOk();
        $this->assertNotSame($oldPassword, $outbox->fresh()->temporary_password);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $oldPassword])->assertUnauthorized();
    }

    public function test_invalid_documents_are_rejected_without_saving_an_application(): void
    {
        Storage::fake('local');
        $this->post('/api/enrollees/apply', ['name' => 'Kim', 'email' => 'kim@example.com', 'contact_number' => '09', 'program' => 'BS Computer Science', 'student_type' => 'new', 'year_level' => 1, 'educational_background' => 'Graduate', 'documents' => [UploadedFile::fake()->create('script.php', 1, 'text/plain')]], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('enrollee_applications', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_duplicate_email_and_unsupported_program_are_rejected(): void
    {
        $this->apply();
        $payload = ['name' => 'Kim', 'email' => ' KIM@example.com ', 'contact_number' => '09', 'program' => 'BS Computer Science', 'student_type' => 'new', 'year_level' => 1, 'educational_background' => 'Graduate', 'documents' => [UploadedFile::fake()->create('credentials.pdf', 20, 'application/pdf')]];
        $this->post('/api/enrollees/apply', $payload, ['Accept' => 'application/json'])->assertUnprocessable();
        $payload['email'] = 'second@example.com';
        $payload['program'] = 'Not a school program';
        $this->post('/api/enrollees/apply', $payload, ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('enrollee_applications', 1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_first_reset_validates_confirmation_and_rejects_expired_existing_sessions(): void
    {
        $a = $this->apply();
        $this->reviewer();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertOk();
        $mail = ProvisioningEmail::firstOrFail();
        $password = $mail->temporary_password;
        $user = $mail->user;
        $this->withHeader('Authorization', 'Bearer '.$this->token($user));
        foreach ([['short', 'short'], ['New-Secure-Password-2026!', 'mismatch'], [$password, $password]] as [$next, $confirm]) {
            $this->postJson('/api/auth/first-login-reset', ['current_password' => $password, 'password' => $next, 'password_confirmation' => $confirm])->assertUnprocessable();
        }
        $this->assertTrue($user->fresh()->must_change_password);
        $user->update(['temporary_password_expires_at' => now()->subMinute()]);
        $this->postJson('/api/auth/first-login-reset', ['current_password' => $password, 'password' => 'New-Secure-Password-2026!', 'password_confirmation' => 'New-Secure-Password-2026!'])->assertForbidden();
    }

    public function test_outbox_failure_rolls_back_user_student_and_approval(): void
    {
        $a = $this->apply();
        $this->reviewer();
        ProvisioningEmail::creating(function () {
            throw new \RuntimeException('Simulated outbox persistence failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson("/api/admin/enrollees/{$a->id}/approve", []);
            $this->fail('Expected the outbox persistence failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated outbox persistence failure', $error->getMessage());
        } finally {
            ProvisioningEmail::flushEventListeners();
        }
        $this->assertDatabaseMissing('users', ['email' => 'kim@example.com']);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_applications', 0);
        $this->assertDatabaseCount('clearances', 0);
        $this->assertSame('PENDING', $a->fresh()->status);
    }

    public function test_mail_template_and_scheduled_sender_clear_expired_secrets(): void
    {
        Mail::fake();
        $a = $this->apply();
        $this->reviewer();
        $this->postJson("/api/admin/enrollees/{$a->id}/approve", [])->assertOk();
        $mail = ProvisioningEmail::firstOrFail();
        $content = (new StudentProvisioned('Kim', 'kim@example.com', 'temporary-test-password'))->render();
        $this->assertStringContainsString('72 hours', $content);
        $this->assertStringContainsString(config('enrollment.portal_url'), $content);
        $mail->user->update(['temporary_password_expires_at' => now()->subMinute()]);
        $this->artisan('enrollees:send-emails')->assertExitCode(0);
        $this->assertNull($mail->fresh()->temporary_password);
        Mail::assertNothingSent();
    }
}
