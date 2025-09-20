<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // counter, gauge, histogram
            $table->json('labels')->nullable();
            $table->decimal('value', 15, 6);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['name', 'recorded_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};