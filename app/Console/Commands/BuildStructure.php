<?php

namespace App\Console\Commands;

use App\Models\{Battalion, Company, Platoon};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildStructure extends Command
{
    protected $signature = 'cadetcore:structure';
    protected $description = 'Create the battalions, companies and platoons listed in config/cadetcore.php (safe to run again)';

    public function handle(): int
    {
        $cfg = config('cadetcore.structure');
        $made = ['battalions' => 0, 'companies' => 0, 'platoons' => 0];

        DB::transaction(function () use ($cfg, &$made) {
            foreach ($cfg['battalions'] as $bn) {
                $b = Battalion::firstOrCreate(['battalion_name' => $bn]);
                $made['battalions'] += (int) $b->wasRecentlyCreated;

                foreach ($cfg['companies'] as $co) {
                    $c = Company::firstOrCreate(['battalion_id' => $b->battalion_id, 'company_name' => $co]);
                    $made['companies'] += (int) $c->wasRecentlyCreated;

                    foreach ($cfg['platoons'] as $pl) {
                        $p = Platoon::firstOrCreate(['company_id' => $c->company_id, 'platoon_name' => $pl]);
                        $made['platoons'] += (int) $p->wasRecentlyCreated;
                    }
                }
            }
        });

        $this->info("Added {$made['battalions']} battalion(s), {$made['companies']} company(ies) and {$made['platoons']} platoon(s).");
        return self::SUCCESS;
    }
}
