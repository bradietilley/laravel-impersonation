<?php

namespace BradieTilley\Impersonation;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImpersonationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('impersonation')
            ->hasConfigFile('impersonation')
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ImpersonationConfig::class, ImpersonationConfig::class);
        $this->app->singleton(ImpersonationManager::class, ImpersonationManager::class);
    }
}
