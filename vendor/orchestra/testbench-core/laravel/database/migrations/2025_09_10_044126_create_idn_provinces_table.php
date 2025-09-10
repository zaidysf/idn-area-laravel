<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idn_provinces', function (Blueprint $table) {
            $table->string('code', 15)->primary()->comment('BPS province code (11, 12, etc.)');
            $table->string('name')->comment('Province name');
            $table->timestamps();
            
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idn_provinces');
    }
};