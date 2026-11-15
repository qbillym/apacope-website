<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('users',fn(Blueprint $t)=>$t->string('role')->default('student'));
Schema::create('school_classes',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->string('section');$t->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();});
Schema::create('students',function(Blueprint $t){$t->id();$t->string('admission_no')->unique();$t->string('name');$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('guardian_phone')->nullable();$t->timestamps();});
Schema::create('attendances',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->date('date');$t->char('status',1);$t->timestamps();$t->unique(['student_id','date']);$t->index(['school_class_id','date']);});
Schema::create('followups',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained();$t->string('contacted');$t->string('method');$t->text('agreed');$t->date('date');$t->timestamps();});
Schema::create('settings',function(Blueprint $t){$t->string('key')->primary();$t->string('value');});}
public function down():void{foreach(['settings','followups','attendances','students','school_classes'] as $x)Schema::dropIfExists($x);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));}};
