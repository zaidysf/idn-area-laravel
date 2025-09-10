<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('idn_islands', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('coordinate')->nullable();
            $table->string('name');
            $table->boolean('is_outermost_small')->default(false);
            $table->boolean('is_populated')->default(false);
            $table->string('regency_code', 5)->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('regency_code');
            $table->index(['is_outermost_small', 'is_populated']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('idn_islands');
    }
};