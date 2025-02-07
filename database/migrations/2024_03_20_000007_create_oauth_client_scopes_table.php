<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_client_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 80);
            $table->string('scope_name', 50);
            $table->timestamps();

            // Foreign keys and indexes
            $table->foreign('client_id')
                ->references('client_id')
                ->on('oauth_clients')
                ->onDelete('cascade');

            $table->foreign('scope_name')
                ->references('name')
                ->on('oauth_scopes')
                ->onDelete('cascade');

            // Composite unique index to prevent duplicate client-scope pairs
            $table->unique(['client_id', 'scope_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_client_scopes');
    }
};
