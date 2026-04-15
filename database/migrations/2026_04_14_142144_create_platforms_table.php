<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // telegram, vk, dzen
            $table->string('name'); // отображаемое имя канала/группы
            $table->string('external_id')->nullable(); // ID в соцсети
            $table->text('access_token')->nullable(); // токен (будет шифроваться)
            $table->json('extra_data')->nullable(); // доп. параметры
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};