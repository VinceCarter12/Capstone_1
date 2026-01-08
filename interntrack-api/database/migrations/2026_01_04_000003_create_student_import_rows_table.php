<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('student_imports')->onDelete('cascade');
            $table->string('student_id')->nullable(); // Student number from Excel
            $table->string('fname');
            $table->string('lname');
            $table->string('email');
            $table->enum('status', ['pending', 'created', 'failed', 'duplicate'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_import_rows');
    }
};
