<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id')->unsigned()->nullable();
            $table->enum('type', ['IMAGE', 'EXCEL', 'CSV', 'WORD', 'PDF', 'AUDIO', 'VIDEO','POWERPOINT'])->nullable();
            $table->enum('reduction_type', ['DEFAULT', 'PERCENTAGE', 'AMOUNT'])->default('DEFAULT')->nullable();
            $table->string('name')->nullable();
            $table->mediumText('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->integer('quantity')->nullable();
            $table->double('price', 255)->default(0)->nullable();
            $table->double('reduction_price', 255)->default(0)->nullable();
            $table->double('tax')->default(0)->nullable();
            $table->string('path')->nullable();
            $table->string('preview_path')->nullable();
            $table->json('keywords')->nullable();
            $table->string('quotation')->nullable();
            $table->string('quotation_owner')->nullable();
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
