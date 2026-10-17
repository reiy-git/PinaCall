<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_number', 10);          // e.g. Q-104, R-082
            $table->integer('daily_sequence');             // sequential number for the day
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            $table->enum('service_stage', ['cashier', 'registrar'])->default('cashier');
            $table->string('window_number')->nullable();
            $table->enum('status', [
                'waiting',         // in queue, not yet called
                'serving',         // currently being served at a window
                'hold',            // processing/async — waiting for document prep
                'ready',           // ready_for_pickup — enters registrar queue
                'completed',       // transaction finished
                'no_show',         // student did not respond when called
                'skipped',         // manually skipped by staff
            ])->default('waiting');
            $table->date('queue_date');                    // the day this token is valid
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['queue_date', 'service_stage', 'status']);
            $table->index(['student_id', 'queue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tokens');
    }
};
