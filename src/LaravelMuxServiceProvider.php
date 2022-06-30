<?php

namespace Jacotheron\LaravelMux;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
class LaravelMuxServiceProvider extends IlluminateServiceProvider
{
    public function register():void
    {
        $configPath = __DIR__.'/../config/laravel-mux.php';
        $this->mergeConfigFrom($configPath, 'laravel-mux');

        $this->app->bind('laravel.mux', function($app){
            return new MuxService();
        });

        $this->app->bind('laravel.muxdata', function($app){
            return new MuxDataService();
        });
    }

    public function boot():void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-mux.php' => base_path('config/laravel-mux'),
        ], 'mux-config');
    }
}