<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('assignments',fn(Blueprint $t)=>$t->unsignedBigInteger('assessment_id')->nullable());
Schema::table('media_items',fn(Blueprint $t)=>$t->boolean('school_wide')->default(false));
Schema::create('media_classes',function(Blueprint $t){$t->id();$t->unsignedBigInteger('media_item_id')->index();$t->unsignedBigInteger('school_class_id');});
Schema::table('portfolios',fn(Blueprint $t)=>$t->string('category')->nullable());}
public function down():void{Schema::dropIfExists('media_classes');Schema::table('assignments',fn(Blueprint $t)=>$t->dropColumn('assessment_id'));Schema::table('media_items',fn(Blueprint $t)=>$t->dropColumn('school_wide'));Schema::table('portfolios',fn(Blueprint $t)=>$t->dropColumn('category'));}};
