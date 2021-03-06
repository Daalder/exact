<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExactIdToOrdersAndCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('exact_id')->nullable()->after('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('exact_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('exact_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('exact_id');
        });
    }
}
