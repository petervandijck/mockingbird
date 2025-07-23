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
        $tableName = config('llamaparse.table_name', 'llama_parse_jobs');
        
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('filename');
            $table->enum('status', ['pending', 'processing', 'success', 'error'])->default('pending');
            $table->text('result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('llamaparse.table_name', 'llama_parse_jobs');
        Schema::dropIfExists($tableName);
    }
};