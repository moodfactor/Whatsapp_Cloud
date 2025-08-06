<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'supervisor', 'agent'])->default('agent');
            $table->json('permissions')->nullable(); // Additional permissions beyond role
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_login')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['email', 'status']);
            $table->index('role');
            $table->index('status');
            
            // Foreign key
            $table->foreign('created_by')->references('id')->on('whatsapp_admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_admins');
    }
};