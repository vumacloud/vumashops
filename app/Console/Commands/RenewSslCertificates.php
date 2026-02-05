<?php

namespace App\Console\Commands;

use App\Services\SslManager;
use Illuminate\Console\Command;

class RenewSslCertificates extends Command
{
    protected $signature = 'ssl:renew';

    protected $description = 'Renew SSL certificates expiring within 30 days';

    public function __construct(protected SslManager $sslManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for expiring SSL certificates...');

        $renewed = $this->sslManager->renewExpiringCertificates();

        $this->info("Renewed {$renewed} certificates");

        return Command::SUCCESS;
    }
}
