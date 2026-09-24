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
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('edit_count')->default(0)->after('status');
            $table->boolean('deletion_requested')->default(false)->after('edit_count');
            $table->text('deletion_reason')->nullable()->after('deletion_requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['edit_count', 'deletion_requested', 'deletion_reason']);
        });
    }
};
