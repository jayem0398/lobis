<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->nullable(); 
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('client_name');
            $table->string('contact_person')->nullable();
            $table->string('position')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('client_address')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('business_style')->nullable();
            $table->string('client_status')->default('Approved'); // Pwede ring enum kung gusto mo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};