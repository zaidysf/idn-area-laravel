<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idn_regencies', function (Blueprint $table) {
            $table->string('code', 15)->primary()->comment('BPS regency code (3173, 3201, etc.)');
            $table->string('province_code', 15)->comment('Parent province code');
            $table->string('name')->comment('Regency/City name');
            $table->timestamps();
            
            $table->foreign('province_code')->references('code')->on('idn_provinces')->onDelete('cascade');
            $table->index(['province_code', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idn_regencies');
    }
};