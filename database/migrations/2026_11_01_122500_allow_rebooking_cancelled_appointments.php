<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            // MySQL requires a separate supporting index for the foreign key
            // before the original unique index can be replaced.
            $table->index('doctor_id', 'appointments_doctor_id_index');
            $table->dropUnique(['doctor_id', 'scheduled_at']);
            $table->unique(
                ['doctor_id', 'scheduled_at', 'status'],
                'appointments_doctor_scheduled_status_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique('appointments_doctor_scheduled_status_unique');
            $table->unique(['doctor_id', 'scheduled_at']);
            $table->dropIndex('appointments_doctor_id_index');
        });
    }
};
