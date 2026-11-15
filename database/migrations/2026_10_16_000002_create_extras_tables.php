<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::create('registers',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->date('date');$t->unsignedBigInteger('user_id')->nullable();$t->timestamp('saved_at')->nullable();$t->unique(['school_class_id','date']);});
Schema::create('resources',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('title');$t->string('type');$t->string('module');$t->string('link')->nullable();$t->string('path')->nullable();$t->unsignedInteger('views')->default(0);$t->timestamps();});
Schema::create('events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->date('date');$t->string('title');$t->string('kind');$t->string('audience');$t->timestamps();});}
public function down():void{foreach(['events','resources','registers'] as $x)Schema::dropIfExists($x);}};
