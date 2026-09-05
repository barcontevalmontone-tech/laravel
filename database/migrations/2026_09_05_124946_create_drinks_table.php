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
        Schema::create('drinks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('brand');
            $table->string('description', 4000)->nullable();
            $table->boolean('alcoholic')->default(false);
            $table->string('type');
            $table->boolean('sold_to_minor')->default(true);
            $table->boolean('sugar_free')->default(false);
            $table->boolean('gluten_free')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drinks');
    }
};
