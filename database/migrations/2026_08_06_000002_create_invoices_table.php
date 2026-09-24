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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('user_id')->comment('Created By')->constrained('users')->onDelete('cascade');
            
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('tax_type')->nullable();

            // ─── DELIVERY COLUMNS ───
            $table->text('delivery_address')->nullable();
            $table->string('delivery_contact_person')->nullable();
            $table->string('delivery_position')->nullable();
            $table->string('delivery_phone')->nullable();
            
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Paid', 'Cancelled'])->default('Draft');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};