<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class StudentProvisioned extends Mailable
{
    public function __construct(public string $studentName, public string $email, public string $temporaryPassword) {}

    public function build(): static
    {
        return $this->subject('Your PINACALL application has been approved')->view('mail.student-provisioned', [
            'portalUrl' => rtrim(config('enrollment.portal_url'), '/'),
        ]);
    }
}
