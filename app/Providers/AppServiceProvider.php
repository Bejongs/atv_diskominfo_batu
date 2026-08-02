<?php

namespace App\Providers;

use App\Models\VideoArchive;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()->headers->get('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view): void {
            $sidebarCategoryCounts = VideoArchive::selectRaw('category, count(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category');

            $view->with([
                'sidebarCategoryCounts' => $sidebarCategoryCounts,
                'sidebarCategories' => VideoArchive::CATEGORIES,
                'sidebarTotalArchives' => $sidebarCategoryCounts->sum(),
                'sidebarActiveCategory' => request('category'),
                'sidebarArchiveOpen' => request()->routeIs('archives.index')
                    || request()->routeIs('archives.show')
                    || request()->routeIs('archives.edit'),
            ]);
        });
    }
}
