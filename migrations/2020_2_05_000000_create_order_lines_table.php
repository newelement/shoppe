<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('order_id');
            $table->bigInteger('product_id');
			$table->decimal('price', 9, 2)->nullable();
            $table->integer('qty')->default(1);
			$table->string('variation', 300)->nullable();
            $table->string('tracking_number', 200)->nullable();
            $table->dateTime('shipped_on', 0)->nullable();
            $table->text('file')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('created');
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
			$table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_lines');
    }
}
