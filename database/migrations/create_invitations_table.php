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
		// TODO: Make the table name configurable.
		// TODO: Add soft delete.

        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('invitable');
            $table->uuid('author_id');
            $table->uuid('code');
            $table->json('data')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('author_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
