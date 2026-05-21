<?php

namespace App\Providers;

use App\Services\AudioService;
use App\Services\FlowService;
use App\Services\Interfaces\IAudioService;
use App\Services\Interfaces\IFlowService;
use App\Services\Interfaces\IItemService;
use App\Services\Interfaces\IModeService;
use App\Services\Interfaces\IProfileService;
use App\Services\Interfaces\IUserService;
use App\Services\ItemService;
use App\Services\ModeService;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(IAudioService::class, AudioService::class);
        $this->app->bind(IFlowService::class, FlowService::class);
        $this->app->bind(IItemService::class, ItemService::class);
        $this->app->bind(IModeService::class, ModeService::class);
        $this->app->bind(IProfileService::class, ProfileService::class);
        $this->app->bind(IUserService::class, UserService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
