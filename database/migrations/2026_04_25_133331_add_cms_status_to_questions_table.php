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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('status')->default('ready')->after('exam_eligible')->index();
            $table->string('question_type')->nullable()->change();
            $table->string('difficulty')->nullable()->default(null)->change();
            $table->text('explanation')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
            $table->string('question_type')->nullable(false)->change();
            $table->string('difficulty')->default('beginner')->nullable(false)->change();
            $table->text('explanation')->nullable(false)->change();
        });
    }
};
