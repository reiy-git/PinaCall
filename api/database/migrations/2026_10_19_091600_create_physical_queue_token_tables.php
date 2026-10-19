<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_queue_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->string('qr_secret_hash', 64)->unique();
            $table->enum('service_stage', ['cashier', 'registrar']);
            $table->enum('status', ['available', 'assigned', 'called', 'missing', 'locked', 'disabled'])->default('available');
            $table->foreignId('assigned_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('assigned_queue_token_id')->nullable()->constrained('queue_tokens')->nullOnDelete();
            $table->foreignId('last_reset_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            $table->index(['service_stage', 'status']);
        });

        Schema::create('physical_queue_token_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_queue_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('queue_token_id')->nullable()->constrained('queue_tokens')->nullOnDelete();
            $table->date('service_date');
            $table->enum('result', ['assigned', 'unavailable', 'blocked']);
            $table->string('reason', 80)->nullable();
            $table->timestamps();

            $table->index(['physical_queue_token_id', 'service_date']);
            $table->index(['student_id', 'physical_queue_token_id', 'service_date']);
        });

        Schema::create('physical_queue_token_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_queue_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('queue_token_id')->nullable()->constrained('queue_tokens')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('event_type', ['assigned', 'called', 'returned', 'missing', 'reset', 'locked', 'disabled']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['physical_queue_token_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_queue_token_events');
        Schema::dropIfExists('physical_queue_token_attempts');
        Schema::dropIfExists('physical_queue_tokens');
    }
};
