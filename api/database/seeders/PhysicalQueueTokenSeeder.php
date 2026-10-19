<?php

namespace Database\Seeders;

use App\Models\PhysicalQueueToken;
use Illuminate\Database\Seeder;

class PhysicalQueueTokenSeeder extends Seeder
{
    private const TOKENS_PER_STAGE = 60;

    public function run(): void
    {
        foreach (['cashier' => 'C', 'registrar' => 'R'] as $stage => $prefix) {
            for ($number = 1; $number <= self::TOKENS_PER_STAGE; $number++) {
                $reference = sprintf('%s-%02d', $prefix, $number);

                $qrSecret = hash_hmac('sha256', $reference, (string) config('app.key'));

                $token = PhysicalQueueToken::firstOrCreate(
                    ['reference' => $reference],
                    [
                        'qr_secret_hash' => hash('sha256', $qrSecret),
                        'qr_secret_encrypted' => $qrSecret,
                        'service_stage' => $stage,
                        'status' => 'available',
                    ],
                );

                if (! $token->qr_secret_encrypted && hash('sha256', $qrSecret) === $token->qr_secret_hash) {
                    $token->update(['qr_secret_encrypted' => $qrSecret]);
                }
            }
        }
    }
}
