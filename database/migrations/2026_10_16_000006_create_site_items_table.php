<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{Schema::create('site_items',function(Blueprint $t){$t->id();$t->string('type');$t->string('title');$t->text('body')->nullable();$t->date('date')->nullable();$t->string('kind')->nullable();$t->timestamps();});}
public function down():void{Schema::dropIfExists('site_items');}};
