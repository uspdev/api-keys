<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uspdev_api_keys', function (Blueprint $table): void {
            $table->id();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uspdev_api_keys');
    }
};

