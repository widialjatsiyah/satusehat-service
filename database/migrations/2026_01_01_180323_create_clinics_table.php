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
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // API key/public code, mis. KLINIK-001
            $table->string('name');
            $table->string('satusehat_client_id')->nullable();
            $table->text('satusehat_client_secret')->nullable(); // terenkripsi
            $table->text('satusehat_access_token')->nullable(); // cache token (terenkripsi)
            $table->timestamp('satusehat_token_expires_at')->nullable();
            $table->text('api_shared_secret')->nullable(); // shared secret untuk HMAC
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('clinics');
    }
};
