<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_id')->nullable()->index();
            $table->string('direction'); // 'inbound' or 'outbound'
            $table->string('recipient')->index();
            $table->string('message_type')->index();
            $table->json('payload');
            // New fields for delivery tracking
            $table->string('delivery_status')->nullable()->index(); // e.g., delivered, read
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}
