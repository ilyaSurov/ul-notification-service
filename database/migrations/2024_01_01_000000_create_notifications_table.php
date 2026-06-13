<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('channel', 10);
            $table->text('message');
            $table->string('recipient', 255);
            $table->smallInteger('priority')->default(0);
            $table->string('status', 20)->default('queued');
            $table->string('idempotency_key', 64)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('recipient');
            $table->index('status');
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
