<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;use App\Models\SiteItem;
class SiteSeeder extends Seeder{public function run():void{
if(SiteItem::count())return;
foreach([['Term examination timetable released','The timetable for the term examinations is now available. Parents and students can read it in the portal.'],['Registration for the new term is open','Parents can create their portal account from the Register page, using the name and class of their child.']] as $n)SiteItem::create(['type'=>'notice','title'=>$n[0],'body'=>$n[1]]);
foreach([[7,'Parents and staff meeting','Meeting'],[14,'Term examinations begin','Exam'],[21,'Inter-school coding competition','Competition']] as $e)SiteItem::create(['type'=>'event','title'=>$e[1],'date'=>today()->addDays($e[0]),'kind'=>$e[2]]);}}
