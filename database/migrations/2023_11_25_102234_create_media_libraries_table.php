<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaLibrariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_libraries', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('article_id')->unsigned()->nullable();
            $table->bigInteger('actualite_id')->unsigned()->nullable();
            $table->bigInteger('my_job_id')->unsigned()->nullable();
            $table->bigInteger('job_application_id')->unsigned()->nullable();
            $table->enum('referral', ['USER', 'ARTICLE', 'ACTUALITE', 'ACTUALITE_FILE', 'JOB'])->nullable();
            $table->enum('type', ['IMAGE', 'EXCEL', 'CSV', 'WORD', 'PDF', 'AUDIO', 'VIDEO', 'OTHER'])->nullable();
            $table->string('path')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('actualite_id')->references('id')->on('actualites')->onDelete('cascade');
            $table->foreign('my_job_id')->references('id')->on('my_jobs')->onDelete('cascade');
            $table->foreign('job_application_id')->references('id')->on('job_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_libraries');
    }
}
