<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{Schema::create('periods',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->unsignedTinyInteger('slot');$t->string('start',5);$t->string('end',5);$t->unique(['school_class_id','slot']);});}
public function down():void{Schema::dropIfExists('periods');}};
