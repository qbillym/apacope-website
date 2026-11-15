<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{Schema::create('announcement_targets',function(Blueprint $t){$t->id();$t->unsignedBigInteger('announcement_id')->index();$t->string('kind');$t->string('value');});}
public function down():void{Schema::dropIfExists('announcement_targets');}};
