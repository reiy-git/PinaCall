<?php

use App\Models\ProvisioningEmail;
use App\Services\ProvisioningMailer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('enrollees:send-emails', function () {
    ProvisioningEmail::whereNull('sent_at')->whereNotNull('temporary_password')->where('available_at', '<=', now())
        ->orderBy('id')->chunkById(100, function ($emails) {
            foreach ($emails as $email) {
                app(ProvisioningMailer::class)->send($email->id);
            }
        });
    $this->info('Processed pending provisioning emails.');
});
Schedule::command('enrollees:send-emails')->everyMinute()->withoutOverlapping();
