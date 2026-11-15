<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;use App\Models\{User,SchoolClass,Attendance,Register,Resource,Event};
class ExtraSeeder extends Seeder{public function run():void{
foreach(Attendance::select('school_class_id','date')->distinct()->get() as $a)Register::firstOrCreate(['school_class_id'=>$a->school_class_id,'date'=>$a->date->toDateString()],['saved_at'=>now()]);
$t=User::where('role','trainer')->first();$h=User::where('role','head')->first();$c=SchoolClass::where('name','L5 SWD')->first();
foreach([['PHP and Laravel basics','Video link','Web Programming','https://developer.mozilla.org/'],['SQL joins handout','Handout','Databases','https://www.w3schools.com/sql/'],['Mobile layout exercise','Exercise','Mobile App Development','https://developer.android.com/']] as $r)Resource::create(['user_id'=>$t->id,'school_class_id'=>$c->id,'title'=>$r[0],'type'=>$r[1],'module'=>$r[2],'link'=>$r[3],'views'=>random_int(5,40)]);
foreach([[7,'Parents and staff meeting','Meeting','Whole school'],[14,'Term examinations begin','Exam','Whole school'],[10,'Networking class test','Class test','L5 SWD'],[21,'Inter-school coding competition','Competition','Whole school'],[45,'Term ends, holidays begin','Holiday','Whole school']] as $e)Event::create(['user_id'=>$h->id,'date'=>today()->addDays($e[0]),'title'=>$e[1],'kind'=>$e[2],'audience'=>$e[3]]);}}
