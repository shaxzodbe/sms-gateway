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
            $table->foreignIdFor(Batch::class)->constrained()->nullOnDelete();
            $table->string('phone');
            $table->boolean('is_valid');
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
