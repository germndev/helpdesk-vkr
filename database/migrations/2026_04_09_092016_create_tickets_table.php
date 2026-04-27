<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed'])->default('new');
            $table->string('priority')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('classification_score')->nullable();
            $table->boolean('needs_manual_review')->default(true);
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
