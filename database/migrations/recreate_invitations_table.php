<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replaces the previous `create_invitations_table` +
     * `alter_invitations_table_add_accepted_and_rejected_at` pair with one
     * clean schema. Any existing `invitations` table is dropped first, so
     * invitation data created with the previous structure is discarded.
     */
    public function up(): void
    {
        // TODO: Make the table name configurable.
        // TODO: Add soft delete.

        Schema::dropIfExists('invitations');

        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('invitable');
            $table->nullableUuidMorphs('author');
            $table->uuid('code')->unique();
            $table->json('data')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('expired_dispatched_at')->nullable();
            $table->timestamps();
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
