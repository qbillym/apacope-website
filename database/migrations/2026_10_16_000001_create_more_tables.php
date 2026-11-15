<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('users',fn(Blueprint $t)=>$t->unsignedBigInteger('student_id')->nullable());
Schema::create('lessons',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->unsignedTinyInteger('day');$t->unsignedTinyInteger('slot');$t->string('subject');$t->string('room');});
Schema::create('marks',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->string('module');$t->unsignedTinyInteger('f')->default(0);$t->unsignedTinyInteger('s')->default(0);$t->unsignedTinyInteger('c')->default(0);$t->timestamps();$t->unique(['student_id','module']);});
Schema::create('announcements',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->string('title');$t->text('body');$t->string('audience');$t->timestamps();});
Schema::create('fees',function(Blueprint $t){$t->id();$t->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();$t->unsignedInteger('invoice');$t->unsignedInteger('paid');});
Schema::create('fee_messages',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained();$t->string('channel');$t->string('body');$t->timestamps();});}
public function down():void{foreach(['fee_messages','fees','announcements','marks','lessons'] as $x)Schema::dropIfExists($x);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('student_id'));}};
