<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Laravel represents enum columns as a PostgreSQL CHECK constraint.
        // Recreate that constraint with the faculty role included.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('student', 'registrar', 'cashier', 'department_head', 'faculty', 'admin'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL enum values cannot be removed safely without recreating the type.
    }
};
