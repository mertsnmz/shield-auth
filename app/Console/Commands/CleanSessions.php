<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSessions extends Command
{
    protected $signature = 'session:clean';
    protected $description = 'Clean old sessions';

    public function handle()
    {
        $this->info('Cleaning old sessions...');

        // Clean sessions older than 30 days
        $oldSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(30)->timestamp)
            ->delete();

        $this->info("Cleaned {$oldSessions} old sessions");
    }
} 