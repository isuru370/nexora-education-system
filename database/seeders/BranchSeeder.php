<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'address1' => 'Mirigama',
                'address2' => 'Sri Lanka',
                'address3' => null,
                'mobile' => '0768971213',
                'email' => 'info@nexorait.lk',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Main branch seeded successfully!');
    }
}