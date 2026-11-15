<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('followups',fn(Blueprint $t)=>$t->unsignedSmallInteger('covered')->default(0));
Schema::table('fees',function(Blueprint $t){$t->string('reason')->nullable();$t->date('promise_date')->nullable();});
Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->unsignedBigInteger('user_id')->nullable();$t->unsignedInteger('amount');$t->string('note')->nullable();$t->timestamps();});
Schema::create('account_requests',function(Blueprint $t){$t->id();$t->string('role');$t->string('name');$t->string('email')->unique();$t->string('password');$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('student_name');$t->unsignedBigInteger('student_id')->nullable();$t->string('status')->default('pending');$t->timestamps();});}
public function down():void{Schema::dropIfExists('account_requests');Schema::dropIfExists('payments');Schema::table('fees',fn(Blueprint $t)=>$t->dropColumn(['reason','promise_date']));Schema::table('followups',fn(Blueprint $t)=>$t->dropColumn('covered'));}};
