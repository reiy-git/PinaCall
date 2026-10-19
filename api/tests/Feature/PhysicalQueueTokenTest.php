<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\DocumentRequest;
use App\Models\PhysicalQueueToken;
use App\Models\PhysicalQueueTokenEvent;
use App\Models\QueueToken;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhysicalQueueTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_activate_a_physical_queue_token(): void
    {
        $this->postJson('/api/queue/activate', [
            'qr_secret' => Str::random(64),
            'document_request_id' => 1,
        ])->assertUnauthorized();
    }

    public function test_initial_cashier_and_registrar_token_inventory_is_seeded_by_migrations(): void
    {
        $this->assertDatabaseCount('physical_queue_tokens', 120);
        $this->assertDatabaseHas('physical_queue_tokens', [
            'reference' => 'C-01',
            'service_stage' => 'cashier',
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('physical_queue_tokens', [
            'reference' => 'R-60',
            'service_stage' => 'registrar',
            'status' => 'available',
        ]);

        $cashier = $this->staff('cashier');
        $this->actingAsPortalUser($cashier);
        $response = $this->getJson('/api/queue/physical-tokens?service_stage=cashier')->assertOk();

        $this->assertCount(60, $response->json('tokens'));
        $this->getJson('/api/queue/physical-tokens/1/qr')
            ->assertOk()
            ->assertJsonPath('reference', 'C-01');
    }

    public function test_active_student_can_activate_available_cashier_token_for_own_eligible_request(): void
    {
        [$user, $student] = $this->student();
        $request = $this->documentRequest($student);
        [$physicalToken, $secret] = $this->physicalToken('cashier');

        $this->actingAsPortalUser($user);
        $activation = $this->postJson('/api/queue/activate', ['qr_secret' => $secret, 'document_request_id' => $request->id])
            ->assertCreated()
            ->assertJsonPath('token.service_stage', 'cashier');

        $this->assertDatabaseHas('queue_tokens', ['student_id' => $student->id, 'document_request_id' => $request->id, 'status' => 'waiting']);
        $this->assertDatabaseHas('physical_queue_tokens', ['id' => $physicalToken->id, 'status' => 'assigned', 'assigned_student_id' => $student->id]);

        $cashier = $this->staff('cashier');
        $this->actingAsPortalUser($cashier);
        $this->getJson("/api/queue/physical-tokens/{$physicalToken->id}/history")
            ->assertOk()
            ->assertJsonPath('token.events.0.queue_ticket.id', $activation->json('token.id'))
            ->assertJsonPath('token.events.0.queue_ticket.document_request.id', $request->id)
            ->assertJsonPath('token.attempts.0.queue_ticket.token_number', $activation->json('token.token_number'))
            ->assertJsonPath('token.attempts.0.student.student_number', $student->student_number);
    }

    public function test_student_cannot_activate_someone_elses_document_request(): void
    {
        [$user, $student] = $this->student();
        [, $otherStudent] = $this->student('other@example.edu', '2026-002');
        $otherRequest = $this->documentRequest($otherStudent);
        [, $secret] = $this->physicalToken('cashier');

        $this->actingAsPortalUser($user);
        $this->postJson('/api/queue/activate', ['qr_secret' => $secret, 'document_request_id' => $otherRequest->id])
            ->assertUnprocessable();
    }

    public function test_student_status_is_private_to_signed_in_student(): void
    {
        $this->getJson('/api/queue/status')->assertUnauthorized();
    }

    public function test_staff_can_list_and_reprint_stage_tokens_without_exposing_qr_secrets_in_inventory(): void
    {
        [$token, $secret] = $this->physicalToken('cashier');
        $cashier = $this->staff('cashier');
        $this->actingAsPortalUser($cashier);

        $this->getJson('/api/queue/physical-tokens?service_stage=cashier')
            ->assertOk()
            ->assertJsonFragment(['reference' => $token->reference])
            ->assertJsonMissingPath('tokens.60.qr_secret')
            ->assertJsonMissingPath('tokens.60.qr_secret_encrypted');

        $this->getJson("/api/queue/physical-tokens/{$token->id}/qr")
            ->assertOk()
            ->assertJsonPath('reference', $token->reference)
            ->assertJsonPath('qr_secret', $secret);

        $registrar = $this->staff('registrar');
        $this->actingAsPortalUser($registrar);
        $this->getJson('/api/queue/physical-tokens?service_stage=cashier')->assertForbidden();
        $this->getJson("/api/queue/physical-tokens/{$token->id}/qr")->assertForbidden();
    }

    public function test_staff_cannot_reset_a_token_with_an_active_ticket_and_reset_history_keeps_request_links(): void
    {
        [$studentUser, $student] = $this->student();
        $request = $this->documentRequest($student);
        [$physicalToken, $secret] = $this->physicalToken('cashier');

        $this->actingAsPortalUser($studentUser);
        $activation = $this->postJson('/api/queue/activate', [
            'qr_secret' => $secret,
            'document_request_id' => $request->id,
        ])->assertCreated();
        $queueTicketId = $activation->json('token.id');

        $cashier = $this->staff('cashier');
        $this->actingAsPortalUser($cashier);
        $this->postJson("/api/queue/physical-tokens/{$physicalToken->id}/reset")->assertUnprocessable();
        $this->postJson("/api/queue/physical-tokens/{$physicalToken->id}/missing")
            ->assertOk()
            ->assertJsonPath('success', true);

        QueueToken::whereKey($queueTicketId)->update(['status' => 'no_show']);
        $this->postJson("/api/queue/physical-tokens/{$physicalToken->id}/reset")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('physical_queue_token_events', [
            'physical_queue_token_id' => $physicalToken->id,
            'event_type' => 'reset',
            'student_id' => $student->id,
            'queue_token_id' => $queueTicketId,
            'staff_id' => $cashier->id,
        ]);
    }

    public function test_paid_document_moves_from_cashier_hold_to_registrar_qr_queue_and_release(): void
    {
        [$studentUser, $student] = $this->student();
        $request = $this->documentRequest($student);
        $request->update(['payment_status' => 'paid', 'status' => 'processing']);

        $cashierTicket = QueueToken::create([
            'token_number' => 'Q-001',
            'daily_sequence' => 1,
            'student_id' => $student->id,
            'document_request_id' => $request->id,
            'service_stage' => 'cashier',
            'status' => 'hold',
            'queue_date' => now()->toDateString(),
        ]);
        [$registrarToken, $registrarSecret] = $this->physicalToken('registrar');

        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/queue/status')
            ->assertOk()
            ->assertJsonPath('active_token.token_number', 'Q-001');

        $registrar = $this->staff('registrar');
        $this->actingAsPortalUser($registrar);
        $this->getJson('/api/queue/physical-tokens?service_stage=registrar')
            ->assertOk()
            ->assertJsonFragment(['reference' => $registrarToken->reference]);
        $this->getJson("/api/queue/physical-tokens/{$registrarToken->id}/qr")
            ->assertOk()
            ->assertJsonPath('qr_secret', $registrarSecret);

        $this->patchJson("/api/documents/{$request->id}/status", ['status' => 'ready_for_pickup'])
            ->assertOk();
        $this->assertDatabaseHas('queue_tokens', ['id' => $cashierTicket->id, 'status' => 'completed']);
        $this->patchJson("/api/documents/{$request->id}/status", ['status' => 'released'])
            ->assertUnprocessable();

        $this->actingAsPortalUser($studentUser);
        $checkIn = $this->postJson('/api/queue/activate', [
            'qr_secret' => $registrarSecret,
            'document_request_id' => $request->id,
        ])->assertCreated()
            ->assertJsonPath('token.service_stage', 'registrar')
            ->assertJsonPath('token.token_number', 'R-001');
        $registrarTicketId = $checkIn->json('token.id');

        $this->getJson('/api/queue/status')
            ->assertOk()
            ->assertJsonPath('active_token.token_number', 'R-001')
            ->assertJsonPath('active_token.service_stage', 'registrar');

        $this->actingAsPortalUser($registrar);
        $this->postJson('/api/queue/call-next', [
            'service_stage' => 'registrar',
            'window_number' => 'Window 1',
        ])->assertOk();
        $this->postJson("/api/queue/{$registrarTicketId}/complete", [
            'action' => 'release_document',
            'physical_token_reference' => $registrarToken->reference,
        ])->assertOk();

        $this->assertDatabaseHas('document_requests', ['id' => $request->id, 'status' => 'released']);
        $this->assertDatabaseHas('queue_tokens', ['id' => $registrarTicketId, 'status' => 'completed']);
        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/queue/status')
            ->assertOk()
            ->assertJsonPath('active_token', null);
    }

    public function test_released_request_does_not_keep_a_stale_cashier_hold_as_active(): void
    {
        [$user, $student] = $this->student();
        $request = $this->documentRequest($student);
        $request->update(['payment_status' => 'paid', 'status' => 'released']);
        QueueToken::create([
            'token_number' => 'Q-001',
            'daily_sequence' => 1,
            'student_id' => $student->id,
            'document_request_id' => $request->id,
            'service_stage' => 'cashier',
            'status' => 'hold',
            'queue_date' => now()->toDateString(),
        ]);

        $this->actingAsPortalUser($user);
        $this->getJson('/api/queue/status')
            ->assertOk()
            ->assertJsonPath('active_token', null);
    }

    private function actingAsPortalUser(User $user): void
    {
        $plainToken = Str::random(80);
        ApiAccessToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(8),
        ]);
        $this->withHeader('Authorization', "Bearer {$plainToken}");
    }

    private function student(string $email = 'student@example.edu', string $number = '2026-001'): array
    {
        $user = User::create([
            'name' => 'Queue Student',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_number' => $number,
            'course_program' => 'BS Information Technology',
            'year_level' => 1,
            'student_type' => 'new',
            'status' => 'active',
        ]);

        return [$user, $student];
    }

    private function documentRequest(Student $student): DocumentRequest
    {
        return DocumentRequest::create([
            'student_id' => $student->id,
            'type' => 'Transcript of Records',
            'document_type' => 'Transcript of Records',
            'purpose' => 'Testing',
            'copies' => 1,
            'fee' => 100,
            'payment_status' => 'unpaid',
            'status' => 'waiting_for_payment',
        ]);
    }

    private function physicalToken(string $stage): array
    {
        $secret = Str::random(64);
        $token = PhysicalQueueToken::create([
            'reference' => strtoupper($stage[0]).'-'.Str::upper(Str::random(6)),
            'qr_secret_hash' => hash('sha256', $secret),
            'qr_secret_encrypted' => $secret,
            'service_stage' => $stage,
            'status' => 'available',
        ]);

        return [$token, $secret];
    }

    private function staff(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Staff',
            'email' => $role.'@example.edu',
            'password' => Hash::make('Password123!'),
            'role' => $role,
        ]);
    }
}
