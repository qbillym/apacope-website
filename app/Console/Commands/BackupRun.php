<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use App\Services\Backup;
class BackupRun extends Command{protected $signature='apacope:backup';protected $description='Back up the school database to storage/app/backups';
public function handle(){$this->info('Backup created: '.Backup::run());return 0;}}
