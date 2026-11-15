<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;use App\Models\{User,SchoolClass,Student,Module,Outcome,Attachment,MediaItem,Assignment};
class Phase10Seeder extends Seeder{public function run():void{
$c=SchoolClass::where('name','L5 SWD')->first();$t=User::where('role','trainer')->first();$h=User::where('role','head')->first();
foreach(Module::where('school_class_id',$c->id)->get() as $m)foreach(['Explain the main concepts of '.$m->name,'Apply '.$m->name.' in a practical task','Test and document the result'] as $i=>$o)Outcome::create(['module_id'=>$m->id,'title'=>$o,'covered'=>$i==0,'covered_on'=>$i==0?today()->subDays(10):null]);
$st=Student::where('school_class_id',$c->id)->orderBy('name')->take(2)->get();
foreach($st as $i=>$s)Attachment::create(['student_id'=>$s->id,'company'=>['Kigali Tech Hub','Rwanda Telecoms'][$i],'supervisor'=>['Mr Habimana','Ms Uwase'][$i],'start'=>today()->subDays(20),'end'=>today()->addDays(40),'status'=>'Ongoing','report'=>false]);
foreach([['Documentaries and technology videos','How the internet works','https://www.youtube.com/'],['Career guidance and entrepreneurship','Careers in software development','https://www.youtube.com/'],['School events and student projects','Maker Space open day highlights','https://www.youtube.com/']] as $x)MediaItem::create(['user_id'=>$h->id,'category'=>$x[0],'title'=>$x[1],'description'=>'Approved by the school.','link'=>$x[2],'status'=>'approved','school_wide'=>true]);
Assignment::create(['school_class_id'=>$c->id,'module'=>'Web Programming','title'=>'Build a login page','instructions'=>'Create a login form with validation and share the link or a zip file.','due_date'=>today()->addDays(7),'user_id'=>$t->id]);}}
