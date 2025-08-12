<?php

namespace SmartDato\BrtTracking\Commands;

use Illuminate\Console\Command;

class BrtCacheWsdlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brt:cache-wsdl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache and patch the BRT WSDL files for HTTPS support';

    /**
     * Execute the console command.
     */
    public function handle(WsdlCache $wsdlCache): int
    {
        $wsdlCache->cacheAndPatch();
        $this->info('BRT WSDL cached & patched.');

        return self::SUCCESS;
    }
}
