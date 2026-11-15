<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration{
public function up():void{
Schema::table('lessons',fn(Blueprint $t)=>$t->unsignedBigInteger('trainer_id')->nullable());
Schema::create('guardians',function(Blueprint $t){$t->id();$t->unsignedBigInteger('student_id')->index();$t->string('name');$t->string('relation')->nullable();$t->string('phone')->nullable();$t->timestamps();});
Schema::create('resource_views',function(Blueprint $t){$t->id();$t->unsignedBigInteger('resource_id');$t->unsignedBigInteger('user_id');$t->unsignedInteger('views')->default(1);$t->timestamps();$t->unique(['resource_id','user_id']);});
Schema::create('asset_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('asset_id')->index();$t->unsignedBigInteger('user_id')->nullable();$t->string('status');$t->string('holder')->nullable();$t->timestamps();});
Schema::table('school_classes',function(Blueprint $t){$t->string('level')->nullable();$t->string('trade')->nullable();});
foreach(DB::table('school_classes')->where('section','tvet')->get() as $c){preg_match('/^L(\d)/',$c->name,$m);DB::table('school_classes')->where('id',$c->id)->update(['trade'=>'Software Development','level'=>isset($m[1])?'Level '.$m[1]:null]);}}
public function down():void{foreach(['asset_logs','resource_views','guardians'] as $x)Schema::dropIfExists($x);Schema::table('lessons',fn(Blueprint $t)=>$t->dropColumn('trainer_id'));Schema::table('school_classes',fn(Blueprint $t)=>$t->dropColumn(['level','trade']));}};
