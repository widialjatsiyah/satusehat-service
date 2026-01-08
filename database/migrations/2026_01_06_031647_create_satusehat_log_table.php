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
        Schema::create('satusehat_log', function (Blueprint $table) {
            $table->id();
            $table->string('response_id')->nullable();
            $table->string('action');
            $table->string('url');
            $table->text('payload')->nullable();  // Changed to nullable to fix the error
            $table->text('response');
            $table->string('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satusehat_log');
    }
};
