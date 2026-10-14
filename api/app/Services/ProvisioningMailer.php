<?php

namespace App\Services;

use App\Mail\StudentProvisioned;
use App\Models\ProvisioningEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProvisioningMailer
{
    public function send(int $id): void
    {
        DB::transaction(function () use ($id) {
            $mail = ProvisioningEmail::with('user')->lockForUpdate()->find($id);
            if (! $mail || $mail->sent_at || $mail->available_at->isFuture()) {
                return;
            }
            $user = $mail->user;
            if ($user->temporary_password_expires_at?->isPast()) {
                $mail->update(['temporary_password' => null]);

                return;
            }
            if (! app()->environment('testing') && ! in_array(config('mail.default'), ['smtp', 'resend', 'ses', 'postmark', 'mailgun'], true)) {
                throw new \RuntimeException('Configure a real mail transport or a local SMTP capture server before sending provisioning mail.');
            }
            if (! $user->must_change_password || ! $user->is_active || ! $mail->temporary_password
                || ! $user->temporary_password_expires_at || $user->temporary_password_expires_at->isPast()) {
                return;
            }
            try {
                Mail::to($user->email)->send(new StudentProvisioned($user->name, $user->email, $mail->temporary_password));
                $mail->update(['sent_at' => now(), 'temporary_password' => null]);
            } catch (\Throwable $error) {
                // No credentials, exception payloads, or SMTP responses in logs/API output.
                $mail->update(['attempts' => $mail->attempts + 1, 'available_at' => now()->addMinutes(5)]);
            }
        });
    }
}
