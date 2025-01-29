<?php

namespace App\Console\Commands;

use App\Models\User;
use DateTime;
use Illuminate\Console\Command;

class DemoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $users = User::where('is_admin', '=', 1)
            ->get();

        $currentDate = new DateTime();

        foreach ($users as $user) {
            if($user->count_bad_request != 0){
                $diff = $currentDate->diffInHours($user->login_well_on);
                if($diff >= 24) {
                    $user->count_bad_request = 0;
                    $user->save();
                    \Log::info("One count reset!");
                }
            }
            \Log::info("No one reset!");
        }
    }
}
