<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physical_queue_tokens', function (Blueprint $table) {
            $table->text('qr_secret_encrypted')->nullable();
        });

        $tokens = DB::table('physical_queue_tokens')->get(['id', 'reference', 'qr_secret_hash']);
        foreach ($tokens as $token) {
            $secret = hash_hmac('sha256', $token->reference, (string) config('app.key'));
            if (hash('sha256', $secret) === $token->qr_secret_hash) {
                DB::table('physical_queue_tokens')
                    ->where('id', $token->id)
                    ->update(['qr_secret_encrypted' => Crypt::encryptString($secret)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('physical_queue_tokens', function (Blueprint $table) {
            $table->dropColumn('qr_secret_encrypted');
        });
    }
};
