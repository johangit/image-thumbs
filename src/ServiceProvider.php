<?php

namespace JohanCode\ImageThumbs;

use Illuminate\Support\ServiceProvider as LServiceProvider;

class ServiceProvider extends LServiceProvider
{

    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/' => config_path() . "/"], 'config');
    }
}