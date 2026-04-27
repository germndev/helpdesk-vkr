<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('classification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['category', 'priority']);
            $table->string('target_value');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('classification_rule_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classification_rule_id')->constrained()->cascadeOnDelete();
            $table->string('phrase');
            $table->unsignedInteger('weight')->default(1);
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('classification_rule_triggers');
        Schema::dropIfExists('classification_rules');
    }
};
