<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::table('students',function(Blueprint $t){$t->string('status')->default('Enrolled');$t->text('previous_record')->nullable();});
Schema::table('resources',function(Blueprint $t){$t->text('description')->nullable();$t->string('outcome')->nullable();$t->date('publish_at')->nullable();});
Schema::create('announcement_reads',function(Blueprint $t){$t->id();$t->unsignedBigInteger('announcement_id');$t->unsignedBigInteger('user_id');$t->unique(['announcement_id','user_id']);});
Schema::create('bookmarks',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('resource_id');$t->unique(['user_id','resource_id']);});
Schema::create('expenses',function(Blueprint $t){$t->id();$t->date('date');$t->string('item');$t->unsignedInteger('amount');$t->string('note')->nullable();$t->unsignedBigInteger('user_id')->nullable();$t->timestamps();});
Schema::create('documents',function(Blueprint $t){$t->id();$t->string('type');$t->string('title');$t->text('body');$t->unsignedBigInteger('user_id');$t->timestamps();});
Schema::create('password_requests',function(Blueprint $t){$t->id();$t->string('email');$t->string('status')->default('pending');$t->timestamps();});
Schema::create('session_plans',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('module');$t->date('date');$t->string('topic');$t->text('objectives')->nullable();$t->boolean('delivered')->default(false);$t->timestamps();});
Schema::create('observations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('observer_id');$t->unsignedBigInteger('trainer_id');$t->date('date');$t->string('rating');$t->text('comments');$t->timestamps();});
Schema::create('cpd_sessions',function(Blueprint $t){$t->id();$t->string('title');$t->date('date');$t->text('attendees')->nullable();$t->unsignedBigInteger('created_by');$t->timestamps();});}
public function down():void{foreach(['cpd_sessions','observations','session_plans','password_requests','documents','expenses','bookmarks','announcement_reads'] as $x)Schema::dropIfExists($x);}};
