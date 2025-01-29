<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_uid')->nullable();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('payment_method')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('postal_code')->nullable();
            $table->double('amount', 255)->default(0);
            $table->string('transaction_id')->nullable();
            $table->boolean('is_shipped')->default(false);
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED', 'SENT', 'RECEIVED', 'RETURNED', 'CANCELLED', 'NOT RECEIVED'])->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('purchases');
    }
}
