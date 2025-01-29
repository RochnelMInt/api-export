<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaqsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['FAQ', 'AGB'])->nullable();
            $table->longText('question_en')->nullable();
            $table->longText('answer_en')->nullable();
            $table->longText('question_fr')->nullable();
            $table->longText('answer_fr')->nullable();
            $table->longText('question_de')->nullable();
            $table->longText('answer_de')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('faqs');
    }
}
