<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
Schema::create('media_items',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->string('category');$t->string('title');$t->text('description')->nullable();$t->string('link')->nullable();$t->string('path')->nullable();$t->string('status')->default('pending');$t->timestamps();});
Schema::create('comments',function(Blueprint $t){$t->id();$t->unsignedBigInteger('resource_id')->index();$t->unsignedBigInteger('user_id');$t->string('body',500);$t->timestamps();});
Schema::create('outcomes',function(Blueprint $t){$t->id();$t->unsignedBigInteger('module_id')->index();$t->string('title');$t->boolean('covered')->default(false);$t->date('covered_on')->nullable();});
Schema::create('attachments',function(Blueprint $t){$t->id();$t->unsignedBigInteger('student_id');$t->string('company');$t->string('supervisor')->nullable();$t->date('start');$t->date('end')->nullable();$t->string('status')->default('Planned');$t->boolean('report')->default(false);$t->text('notes')->nullable();$t->timestamps();});
Schema::create('portfolios',function(Blueprint $t){$t->id();$t->string('kind');$t->unsignedBigInteger('student_id')->nullable();$t->unsignedBigInteger('user_id');$t->string('title');$t->text('description')->nullable();$t->string('link')->nullable();$t->string('path')->nullable();$t->timestamps();});
Schema::table('assessments',fn(Blueprint $t)=>$t->date('deadline')->nullable());
Schema::create('assignments',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->cascadeOnDelete();$t->string('module');$t->string('title');$t->text('instructions')->nullable();$t->date('due_date');$t->unsignedBigInteger('user_id');$t->timestamps();});
Schema::create('submissions',function(Blueprint $t){$t->id();$t->foreignId('assignment_id')->constrained()->cascadeOnDelete();$t->unsignedBigInteger('student_id');$t->text('content')->nullable();$t->string('link')->nullable();$t->string('path')->nullable();$t->string('grade')->nullable();$t->string('feedback')->nullable();$t->timestamps();$t->unique(['assignment_id','student_id']);});
Schema::create('staff_attendance',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->date('date');$t->char('status',1);$t->unsignedBigInteger('recorded_by')->nullable();$t->unique(['user_id','date']);});
Schema::create('role_denies',function(Blueprint $t){$t->id();$t->string('role');$t->string('feature');$t->unique(['role','feature']);});
}
public function down():void{Schema::table('assessments',fn(Blueprint $t)=>$t->dropColumn('deadline'));foreach(['role_denies','staff_attendance','submissions','assignments','portfolios','attachments','outcomes','comments','media_items'] as $x)Schema::dropIfExists($x);}};
