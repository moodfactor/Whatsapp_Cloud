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
        Schema::create('whatsapp_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Contact name
            $table->string('receiver_id', 50); // Phone number
            $table->text('last_message')->nullable();
            $table->timestamp('last_msg_time')->nullable();
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed'])->default('new');
            $table->unsignedBigInteger('assigned_to')->nullable(); // Assigned admin
            $table->integer('unread')->default(0); // Unread message count
            $table->json('metadata')->nullable(); // Additional data (country, etc.)
            $table->timestamps();
            
            // Indexes
            $table->index(['receiver_id', 'status']);
            $table->index('assigned_to');
            $table->index('status');
            $table->index('last_msg_time');
            
            // Foreign key
            $table->foreign('assigned_to')->references('id')->on('whatsapp_admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_interactions');
    }
};