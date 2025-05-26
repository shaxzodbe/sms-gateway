<?php

use App\Models\Batch;
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
        Schema::create('batch_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->references('id')->on('batches')->onDelete('cascade');
            $table->string('phone')->index();
            $table->boolean('is_valid')->default(true);
            $table->json('placeholders')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_recipients');
    }
};
