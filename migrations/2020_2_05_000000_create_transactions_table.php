<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('order_id');
            $table->bigInteger('order_line_id')->nullable();
			$table->string('transaction_ref_id', 100);
            $table->decimal('amount', 9, 2);
            $table->decimal('tax_amount', 9, 2)->nullable();
            $table->decimal('shipping_amount', 9, 2)->nullable();
            $table->integer('qty')->nullable();
            $table->string('transaction_type', 10);
            $table->text('notes')->nullable();
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
			$table->index('order_id');
            $table->index('line_id');
            $table->index('transaction_ref_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transactions');
    }
}
