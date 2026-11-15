<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('users',fn(Blueprint $t)=>$t->boolean('active')->default(true));
Schema::create('modules',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('name');$t->unique(['school_class_id','name']);});
Schema::create('assessments',function(Blueprint $t){$t->id();$t->foreignId('module_id')->constrained()->cascadeOnDelete();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained();$t->string('title');$t->string('kind');$t->unsignedSmallInteger('max');$t->date('date');$t->timestamp('locked_at')->nullable();$t->timestamps();});
Schema::create('scores',function(Blueprint $t){$t->id();$t->foreignId('assessment_id')->constrained()->cascadeOnDelete();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->decimal('score',5,1);$t->unique(['assessment_id','student_id']);});
Schema::create('audit_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id')->nullable();$t->string('action');$t->string('detail')->nullable();$t->timestamps();});
Schema::create('assets',function(Blueprint $t){$t->id();$t->string('name');$t->string('kind');$t->string('location');$t->string('status')->default('In store');$t->string('holder')->nullable();$t->timestamps();});}
public function down():void{foreach(['assets','audit_logs','scores','assessments','modules'] as $x)Schema::dropIfExists($x);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('active'));}};
