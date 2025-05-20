<?php

use App\Enums\MessageStatus;
use App\Models\Batch;
use App\Models\Provider;
use App\Models\Template;
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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId(Batch::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Template::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Provider::class)->nullable()->constrained()->nullOnDelete();
            $table->string('phone');
            $table->text('message');
            $table->enum('status', MessageStatus::values())
                ->default(MessageStatus::PENDING->value)
                ->index();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
