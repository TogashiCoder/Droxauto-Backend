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
        Schema::create('dapartos', function (Blueprint $table) {
            $table->id();
            $table->string('tiltle')->nullable();
            $table->string('teilemarke_teilenummer', 255);
            $table->decimal('preis', 10, 2);
            $table->string('interne_artikelnummer', 100)->unique();
            $table->integer('zustand');
            $table->integer('pfand');
            $table->integer('versandklasse');
            $table->integer('lieferzeit');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('teilemarke_teilenummer');
            $table->index('preis');
            $table->index('interne_artikelnummer');
            $table->index('zustand');
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dapartos');
    }
};
