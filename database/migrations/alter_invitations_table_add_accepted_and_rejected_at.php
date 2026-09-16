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
        if (! Schema::hasColumn('invitations', 'used')) {
            return;
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('data');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
        });

        DB::table('invitations')->where('used', true)->update(['accepted_at' => DB::raw('updated_at')]);

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('invitations', 'used')) {
            return;
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->boolean('used')->default(false)->after('data');
        });

        DB::table('invitations')->whereNotNull('accepted_at')->update(['used' => true]);

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'rejected_at']);
        });
    }
};