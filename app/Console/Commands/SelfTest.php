<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Http\Request;use App\Services\Menu;use App\Models\{User,Student};
class SelfTest extends Command{protected $signature='apacope:selftest';protected $description='Opens every page for every demo role and reports problems';
public function handle(){$k=$this->laravel->make(\Illuminate\Contracts\Http\Kernel::class);$tot=0;$bad=0;
foreach(['admin','head','dosb','dost','trainer','student','parent','accountant','storekeeper'] as $role){$u=User::where('email',"$role@apacope.test")->first();if(!$u){$this->warn("No demo account for $role. Run: php artisan db:seed");continue;}
app('auth')->forgetGuards();app('auth')->guard('web')->setUser($u);
$urls=collect(Menu::items())->filter(fn($m)=>!$m[4]||in_array($role,$m[4]))->pluck(2)->values()->all();
$st=in_array($role,['student','parent'])?Student::find($u->student_id):Student::whereIn('school_class_id',$u->classes()->pluck('id'))->first();if($st&&in_array($role,['student','parent','trainer','dosb','dost','head']))$urls[]='/report/'.$st->id;
$this->line("<options=bold>$role</>");
foreach($urls as $url){$tot++;try{$res=$k->handle(Request::create($url,'GET'));$c=$res->getStatusCode();$msg=$c>=500?(property_exists($res,'exception')&&$res->exception?$res->exception->getMessage():'server error'):'';}catch(\Throwable $e){$c=500;$msg=$e->getMessage();}
if($c===200){$this->line("  OK    $url");}else{$bad++;$this->error("  FAIL  $url  ->  HTTP $c  ".mb_substr($msg,0,200));}}}
$this->newLine();$this->info(($tot-$bad)."/$tot pages opened without errors.");if($bad)$this->warn('Send the FAIL lines above to your developer.');return $bad?1:0;}}
