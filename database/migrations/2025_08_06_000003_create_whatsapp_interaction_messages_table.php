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
        Schema::create('whatsapp_interaction_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('interaction_id');
            $table->text('message')->nullable();
            $table->enum('type', ['text', 'image', 'audio', 'video', 'document', 'sticker', 'location', 'contact'])->default('text');
            $table->enum('nature', ['sent', 'received']); // Direction of message
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->string('url', 500)->nullable(); // For media messages
            $table->string('filename')->nullable(); // Original filename for documents
            $table->string('mime_type')->nullable(); // MIME type for media
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->string('whatsapp_message_id')->nullable(); // WhatsApp's message ID
            $table->timestamp('time_sent')->nullable();
            $table->json('metadata')->nullable(); // Additional message data
            $table->timestamps();
            
            // Indexes
            $table->index(['interaction_id', 'time_sent']);
            $table->index('nature');
            $table->index('type');
            $table->index('status');
            $table->index('whatsapp_message_id');
            
            // Foreign key
            $table->foreign('interaction_id')->references('id')->on('whatsapp_interactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_interaction_messages');
    }
};