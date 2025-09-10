<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idn_districts', function (Blueprint $table) {
            $table->string('code', 15)->primary()->comment('BPS district code (3173060, etc.)');
            $table->string('regency_code', 15)->comment('Parent regency code');
            $table->string('name')->comment('District name');
            $table->timestamps();
            
            $table->foreign('regency_code')->references('code')->on('idn_regencies')->onDelete('cascade');
            $table->index(['regency_code', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idn_districts');
    }
};