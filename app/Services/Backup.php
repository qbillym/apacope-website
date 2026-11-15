<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
class Backup{
public static function dir(){return storage_path('app/backups');}
public static function tables(){return collect(Schema::getTables())->pluck('name')->reject(fn($n)=>in_array($n,['sessions','cache','cache_locks','jobs','job_batches','failed_jobs','migrations']))->values();}
public static function run():string{@mkdir(self::dir(),0775,true);$d=['made'=>now()->toIso8601String(),'tables'=>[]];
foreach(self::tables() as $t)$d['tables'][$t]=DB::table($t)->get()->map(fn($r)=>(array)$r)->all();
$f='apacope-'.now()->format('Ymd-His').'.json.gz';file_put_contents(self::dir().'/'.$f,gzencode(json_encode($d)));
collect(glob(self::dir().'/*.json.gz'))->sort()->slice(0,-30)->each(fn($x)=>@unlink($x));return $f;}
public static function files(){return collect(glob(self::dir().'/*.json.gz'))->sortDesc()->map(fn($x)=>['name'=>basename($x),'size'=>round(filesize($x)/1024),'at'=>date('d M Y H:i',filemtime($x))])->values();}
public static function read($n){$f=self::dir().'/'.basename($n);abort_unless(is_file($f),404);return json_decode(gzdecode(file_get_contents($f)),true);}
public static function verify($n):array{$d=self::read($n);$r=[];foreach($d['tables'] as $t=>$rows)$r[]=['t'=>$t,'file'=>count($rows),'live'=>Schema::hasTable($t)?DB::table($t)->count():null];return $r;}
public static function restore($n):void{$d=self::read($n);Schema::disableForeignKeyConstraints();
foreach($d['tables'] as $t=>$rows){if(!Schema::hasTable($t))continue;DB::table($t)->delete();foreach(array_chunk($rows,200) as $c)DB::table($t)->insert($c);
if(DB::getDriverName()==='pgsql'&&Schema::hasColumn($t,'id')){try{DB::statement("select setval(pg_get_serial_sequence('$t','id'), coalesce((select max(id) from $t),1))");}catch(\Throwable $e){}}}
Schema::enableForeignKeyConstraints();}}
