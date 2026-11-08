<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The web accounts tests sign in with (TestAccount); an app brings its own users. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_accounts');
    }
};
