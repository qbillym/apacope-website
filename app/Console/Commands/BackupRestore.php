<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use App\Services\Backup;
class BackupRestore extends Command{protected $signature='apacope:restore {file} {--force}';protected $description='Restore the database from a backup file (replaces ALL current data)';
public function handle(){if(!$this->option('force')&&!$this->confirm('This replaces ALL current data with the backup. Continue?'))return 1;Backup::restore($this->argument('file'));$this->info('Restored from '.$this->argument('file'));return 0;}}
