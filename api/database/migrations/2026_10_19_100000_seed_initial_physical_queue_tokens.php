<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $tokens = [];

        foreach (['cashier' => 'C', 'registrar' => 'R'] as $stage => $prefix) {
            for ($number = 1; $number <= 60; $number++) {
                $reference = sprintf('%s-%02d', $prefix, $number);
                $secret = hash_hmac('sha256', $reference, (string) config('app.key'));

                $tokens[] = [
                    'reference' => $reference,
                    'qr_secret_hash' => hash('sha256', $secret),
                    'qr_secret_encrypted' => Crypt::encryptString($secret),
                    'service_stage' => $stage,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($tokens, 100) as $batch) {
            DB::table('physical_queue_tokens')->insertOrIgnore($batch);
        }
    }

    public function down(): void
    {
        // Keep token records on rollback; they may already be in physical use.
    }
};
