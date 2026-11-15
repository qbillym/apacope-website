<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use App\Services\Limits;
class Check extends Command{protected $signature='apacope:check';protected $description='Check PHP upload limits and the setup of the school platform';
public function handle(){$ok=fn($t,$g,$h='')=>$this->line(($g?'  OK    ':'  FIX   ').$t.($g||!$h?'':'  ->  '.$h));
$mb=Limits::mb();$ok("Uploads allow {$mb} MB (upload_max_filesize ".ini_get('upload_max_filesize').", post_max_size ".ini_get('post_max_size').")",$mb>=100,'start the site with start.bat, or raise both values to 100M in php.ini');
foreach(['zlib'=>'backups','fileinfo'=>'file uploads','mbstring'=>'text handling'] as $e=>$why)$ok("PHP extension $e ($why)",extension_loaded($e),"enable extension=$e in php.ini");
$ok('A database driver (pdo_mysql or pdo_pgsql)',extension_loaded('pdo_mysql')||extension_loaded('pdo_pgsql'),'enable pdo_mysql or pdo_pgsql in php.ini');
$ok('Uploaded files link (public/storage)',is_dir(public_path('storage')),'run: php artisan storage:link');
$ok('Backup folder is writable',is_writable(storage_path('app')),'make storage/app writable');
$this->line('Note: this check reads the command-line php.ini. The website may use a different one.');return 0;}}
