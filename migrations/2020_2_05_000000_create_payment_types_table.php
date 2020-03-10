<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('billing_id', 100);
            $table->string('payment_type', 20);
            $table->string('card_brand', 20)->nullable();
            $table->string('last_four', 4)->nullable();
            $table->string('payment_connector', 100);
			$table->boolean('default')->default(0);
            $table->timestamps();
            $table->softDeletes();
			$table->index('user_id');
            $table->index('connector_name');
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payment_types');
    }
}
