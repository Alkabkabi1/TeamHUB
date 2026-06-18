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
        Schema::create('committee_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->dateTime('requested_at')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'committee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_memberships');
    }
};
