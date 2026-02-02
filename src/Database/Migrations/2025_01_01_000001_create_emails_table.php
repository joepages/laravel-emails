<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->string('emailable_type');
            $table->unsignedBigInteger('emailable_id');

            // Email classification
            $table->string('type', 50)->default('personal');
            $table->boolean('is_primary')->default(false);

            // Email field
            $table->string('email');

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Extensibility
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['emailable_type', 'emailable_id'], 'emails_emailable_index');
            $table->index('type');
            $table->index('is_primary');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
