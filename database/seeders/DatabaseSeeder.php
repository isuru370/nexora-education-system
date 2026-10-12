<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserTypesTableSeeder::class,
            BranchSeeder::class,
            SystemUsersTableSeeder::class,
            GradesTableSeeder::class,
            BanksTableSeeder::class,
            ClassCategoryTableSeeder::class,
        ]);
    }
}
