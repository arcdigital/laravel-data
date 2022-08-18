<?php

namespace Spatie\LaravelData;

use Spatie\LaravelData\Commands\DataMakeCommand;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDataServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-data')
            ->hasCommand(DataMakeCommand::class)
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton(
            DataConfig::class,
            fn () => new DataConfig(config('data'))
        );

        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->app->beforeResolving(BaseData::class, function ($class, $parameters, $app) {
            if ($app->has($class)) {
                return;
            }

            if($class::createByContainerInjection() === false){
                return;
            }

            $app->bind(
                $class,
                fn ($container) => $class::from($container['request'])
            );
        });
    }
}
