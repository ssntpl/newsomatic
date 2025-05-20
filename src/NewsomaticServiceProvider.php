<?php

namespace Ssntpl\Newsomatic;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\ServiceProvider;

class NewsomaticServiceProvider extends ServiceProvider {

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/newsomatic.php' => config_path('newsomatic.php'),
        ]);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/newsomatic.php',
            'newsomatic'
        );
    }
}