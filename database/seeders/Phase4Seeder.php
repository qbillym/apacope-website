<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;use App\Models\{Period,User,SchoolClass,Student,Module,Assessment,Score,Asset};
class Phase4Seeder extends Seeder{public function run():void{
$t=User::where('role','trainer')->first();foreach(SchoolClass::all() as $c0)foreach([[1,'08:00','09:40'],[2,'10:30','12:10'],[3,'13:00','14:40'],[4,'15:00','16:30']] as $q)Period::create(['school_class_id'=>$c0->id,'slot'=>$q[0],'start'=>$q[1],'end'=>$q[2]]);$gen=['Mathematics','English','Kinyarwanda','Science'];
$mods=['L3 SWD'=>['HTML and CSS','Computer Basics','Mathematics'],'L4 SWD'=>['JavaScript','Networking','Databases'],'L5 SWD'=>['Web Programming','Databases','Mobile App Development','Python Programming','PHP Programming']];
foreach(SchoolClass::all() as $c){$st=Student::where('school_class_id',$c->id)->get();
foreach($mods[$c->name]??$gen as $m){$mod=Module::create(['school_class_id'=>$c->id,'name'=>$m,'trainer_id'=>$c->name==='L5 SWD'&&$m==='PHP Programming'?$t->id:null]);
foreach([['Formative 1','Formative',20],['Formative 2','Formative',20],['Summative 1','Summative',40]] as $k=>$a){$as=Assessment::create(['module_id'=>$mod->id,'school_class_id'=>$c->id,'user_id'=>$t->id,'title'=>$a[0],'kind'=>$a[1],'max'=>$a[2],'date'=>today()->subDays(30-$k*10),'locked_at'=>now()]);
$rows=[];foreach($st as $s)$rows[]=['assessment_id'=>$as->id,'student_id'=>$s->id,'score'=>random_int((int)($a[2]*.45),$a[2])];Score::insert($rows);}}}
foreach([['Laptop HP-014','Computer','Lab 1','In store'],['Projector EP-03','Projector','Staff room','Borrowed'],['Arduino kit 07','Electronics','Maker Space','Damaged'],['Textbook: Databases','Textbook','Library','In store']] as $a)Asset::create(['name'=>$a[0],'kind'=>$a[1],'location'=>$a[2],'status'=>$a[3],'holder'=>$a[3]==='Borrowed'?'Trainer Kamanzi':null]);}}
