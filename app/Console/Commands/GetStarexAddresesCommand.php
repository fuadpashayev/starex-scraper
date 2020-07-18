<?php

namespace App\Console\Commands;

use App\Http\Controllers\ScrapeController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GetStarexAddresesCommand extends Command
{
    protected $signature = 'get:addresses';
    protected $description = 'Get valid addresses from Starex.az';

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        // getting username from console
        $username = $this->ask('Enter Starex.az username');

        // getting password from console
        $password = $this->ask('Enter Starex.az password');

        $this->info("Authenticating...");

        // calling "ScraperController" in order to start process
        $scraper = new ScrapeController();

        // signing in
        $scraper = $scraper->login($username,$password,function($status){
            if($status==="failed"){
                $this->info("Authentication failed! Please try again.");
                exit;
            }else{
                $this->info("Authentication succeed! Addresses are loading...");
            }
        });

        // getting addresses
        $addresses = $scraper->getAddresses();
        $this->info($addresses);
        return 0;
    }
}
