<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('login')->unique()->after('last_name');
            $table->enum('role', ['user', 'support', 'admin'])->default('user')->after('password');
            $table->string('avatar_path')->nullable()->after('role');

            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'login', 'role', 'avatar_path']);
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
