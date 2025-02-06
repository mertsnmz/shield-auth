<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 40)->unique();
            $table->string('client_id', 80);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamp('expires');
            $table->string('scope', 4000)->nullable();
            $table->timestamps();

            $table->foreign('client_id')
                  ->references('client_id')
                  ->on('oauth_clients')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_access_tokens');
    }
}; 