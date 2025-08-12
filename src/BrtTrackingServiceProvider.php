<?php

namespace SmartDato\BrtTracking;

use SmartDato\BrtTracking\Commands\BrtCacheWsdlCommand;
use SmartDato\BrtTracking\Support\WsdlCache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BrtTrackingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('brt-tracking')
            ->hasConfigFile()
            ->hasCommand(BrtCacheWsdlCommand::class);
    }

    public function packageBooted(): void
    {
        // Bind the BRT tracking client as a singleton in the container
        $this->app->singleton(BrtTrackingClient::class, function ($app) {
            /** @var array $config */
            $config = $app['config']->get('brt-tracking');

            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $app->make(\Psr\Log\LoggerInterface::class);

            // Instantiate a WsdlCache for optional local caching and patching
            $wsdlCache = new WsdlCache;

            return new BrtTrackingClient($config, $logger, $wsdlCache);
        });
    }
}
