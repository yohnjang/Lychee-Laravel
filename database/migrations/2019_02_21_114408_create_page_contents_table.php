<?php
/** @noinspection PhpUndefinedClassInspection */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePageContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->increments('id');
	        $table->integer('page_id')->unsigned();
	        $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
	        $table->text('content');
	        $table->string('class', 150);
	        $table->enum('type',['div','img']);
	        $table->integer('order')->default(0);
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
	    if(env('DB_DROP_CLEAR_TABLES_ON_ROLLBACK',false)) {
		    Schema::dropIfExists('page_contents');
	    }
    }
}
