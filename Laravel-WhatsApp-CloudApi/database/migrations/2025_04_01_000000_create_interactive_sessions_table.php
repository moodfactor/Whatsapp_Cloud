<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInteractiveSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('interactive_sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->string('recipient');
            $table->string('status')->default('pending');
            $table->json('message_payload');
            $table->json('response')->nullable();
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('interactive_sessions');
    }
}
