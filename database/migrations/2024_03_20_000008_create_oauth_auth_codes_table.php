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
        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('client_id', 80);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('scopes', 4000)->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->string('redirect_uri', 2000);
            $table->timestamps();

            // Foreign key for client_id
            $table->foreign('client_id')
                  ->references('client_id')
                  ->on('oauth_clients')
                  ->onDelete('cascade');

            // Index for performance
            $table->index(['client_id', 'user_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_auth_codes');
    }
};
