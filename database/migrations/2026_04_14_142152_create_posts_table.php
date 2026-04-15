<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->string('topic')->nullable(); // тема для AI
            $table->string('status')->default('draft'); // draft, generated, ready, scheduled, published, error
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('media')->nullable(); // массив путей к файлам
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};