<?php

namespace App\Providers;

use App\Services\RiderWorkStatusService;
use App\Services\SuperAdminDashboardService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\AdminLoginDevice;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        /*Event::listen(MigrationsStarted::class, function (){
            DB::statement('SET SESSION sql_require_primary_key=0');
        });

        Event::listen(MigrationsEnded::class, function (){
            DB::statement('SET SESSION sql_require_primary_key=1');            
        });*/
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            $request = request();
            if ($request->getHttpHost()) {
                URL::forceRootUrl($request->getSchemeAndHttpHost());
            }
        }

        if (! $this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            try {
                app(RiderWorkStatusService::class)->resetExpiredOffRiders();
            } catch (\Throwable $e) {
                // Ignore if users table is not ready yet.
            }
        }

        View::composer(['super-admin.*', 'super-admin.*.*'], function ($view) {
            if (! auth()->check() || ! isSuperAdmin(auth()->user())) {
                return;
            }
            $service = app(SuperAdminDashboardService::class);
            $period = $service->periodFromRequest(request());
            $view->with('saPeriod', $period);
            $view->with('saShared', $service->sharedBar($period));
        });
    }
}
