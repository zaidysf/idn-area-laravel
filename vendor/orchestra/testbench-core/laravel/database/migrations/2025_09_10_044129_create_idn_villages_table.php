<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idn_villages', function (Blueprint $table) {
            $table->string('code', 15)->primary()->comment('BPS village code (3173060001, etc.)');
            $table->string('district_code', 15)->comment('Parent district code');
            $table->string('name')->comment('Village/Kelurahan name');
            $table->timestamps();
            
            $table->foreign('district_code')->references('code')->on('idn_districts')->onDelete('cascade');
            $table->index(['district_code', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idn_villages');
    }
};