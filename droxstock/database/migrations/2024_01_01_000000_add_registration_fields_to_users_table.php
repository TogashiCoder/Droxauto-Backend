<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('pending')->after('email_verified_at');
            $table->timestamp('registration_date')->nullable()->after('registration_status');
            $table->text('admin_notes')->nullable()->after('registration_date');
            $table->timestamp('approved_at')->nullable()->after('admin_notes');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_status',
                'registration_date',
                'admin_notes',
                'approved_at',
                'rejected_at'
            ]);
        });
    }
};
