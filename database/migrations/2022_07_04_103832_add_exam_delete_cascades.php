<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExamDeleteCascades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exam_sections', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });

        Schema::table('exam_session_sections', function (Blueprint $table) {
            $table->foreign('exam_session_id')->references('id')->on('exam_sessions')->onDelete('cascade');
        });

        Schema::table('exam_session_questions', function (Blueprint $table) {
            $table->foreign('exam_session_id')->references('id')->on('exam_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
