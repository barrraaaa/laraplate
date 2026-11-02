<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('laraplate:demo {--fresh : Remove existing demo data first}')]
#[Description('Seed clearly-labeled demo data: plans, roles, sample organizations and billing activity.')]
class LaraplateDemo extends Command
{
    public function handle(): int
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(PlanSeeder::class);

        (new DemoSeeder)->run($this->option('fresh'));

        $this->newLine();
        $this->info('Demo data ready — log in with demo@laraplate.test / password (platform admin).');

        return self::SUCCESS;
    }
}
