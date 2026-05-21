<?php

namespace App\Providers;

use App\Repositories\AudioRepository;
use App\Repositories\FlowRepository;
use App\Repositories\Interfaces\IAudioRepository;
use App\Repositories\Interfaces\IFlowRepository;
use App\Repositories\Interfaces\IItemRepository;
use App\Repositories\Interfaces\IModeRepository;
use App\Repositories\Interfaces\IProfileRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\ItemRepository;
use App\Repositories\ModeRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(IAudioRepository::class, AudioRepository::class);
        $this->app->bind(IFlowRepository::class, FlowRepository::class);
        $this->app->bind(IItemRepository::class, ItemRepository::class);
        $this->app->bind(IModeRepository::class, ModeRepository::class);
        $this->app->bind(IProfileRepository::class, ProfileRepository::class);
        $this->app->bind(IUserRepository::class, UserRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
