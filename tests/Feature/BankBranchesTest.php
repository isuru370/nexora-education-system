<?php

namespace Tests\Feature;

use App\Services\System\DeployCheckService;
use Database\Seeders\BankBranchesSeeder;
use Database\Seeders\BanksTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Banks + 3,630 bank branches go into every database (nexora:deploy). */
class BankBranchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_banks_and_branches_are_added_once(): void
    {
        (new BanksTableSeeder())->run();
        $result = (new BankBranchesSeeder())->run();

        $this->assertSame(44, DB::table('banks')->count());
        $this->assertSame(3630, $result['added']);
        $this->assertSame([], $result['unknown_banks']);
        $this->assertSame(3630, DB::table('bank_branches')->count());

        $boc = DB::table('banks')->where('bank_code', '7010')->value('id');
        $this->assertTrue(DB::table('bank_branches')->where('bank_id', $boc)->where('branch_name', 'Agalawatta')->exists());
        $this->assertSame("People's Leasing & Finance PLC", DB::table('banks')->where('bank_code', '7922')->value('bank_name'));

        // run again: nothing new
        (new BanksTableSeeder())->run();
        $this->assertSame(0, (new BankBranchesSeeder())->run()['added']);
        $this->assertSame(44, DB::table('banks')->count());
        $this->assertSame(3630, DB::table('bank_branches')->count());
    }

    public function test_existing_database_gets_the_missing_ones(): void
    {
        // an old install: banks with the broken name, a few branches
        (new BanksTableSeeder())->run();
        DB::table('banks')->where('bank_code', '7922')->update(['bank_name' => 'Peopleâ€™s Leasing & Finance PLC']);
        DB::table('banks')->where('bank_code', '7816')->delete();
        (new BankBranchesSeeder())->run();
        DB::table('bank_branches')->limit(10)->delete();

        $check = app(DeployCheckService::class)->referenceData(true);

        $this->assertSame('ok', $check['status'], $check['message']);
        // 10 deleted + the branches of the deleted bank (cascade)
        $this->assertStringStartsWith('44 banks, 3630 branches (', $check['message']);
        $this->assertSame(44, DB::table('banks')->count());
        $this->assertSame("People's Leasing & Finance PLC", DB::table('banks')->where('bank_code', '7922')->value('bank_name'));
    }

    public function test_check_only_reports_without_adding(): void
    {
        (new BanksTableSeeder())->run();

        $check = app(DeployCheckService::class)->referenceData(false);

        $this->assertSame('warn', $check['status']);
        $this->assertSame(0, DB::table('bank_branches')->count());
    }
}
