<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MoonShine\MoonShineAuth;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moonshine_socialites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('moonshine_user_id')
                ->constrained(
                    MoonShineAuth::model()?->getTable(),
                    MoonShineAuth::model()?->getKeyName()
                )
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('driver');
            $table->string('identity');

            $table->unique(['driver', 'identity']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moonshine_socialites');
    }
};
