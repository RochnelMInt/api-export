<?php

namespace App\Console\Commands;

use App\Models\User;
use DateTime;
use Illuminate\Console\Command;

class DeleteFakeAccountCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteFakeAccount:cron';

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

        $users = User::where('is_admin', '=', 0)
            ->get();

        foreach ($users as $user) {
            $currentDate = new DateTime();
            $diff = $currentDate->diffInDays($user->created_at);

            if($user->email_verified_at == null && $diff > 3) {
                $user->delete();
            }
        }
    }
}
