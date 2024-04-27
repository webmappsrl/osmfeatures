<?php

namespace App\Providers;

use App\Nova\Dashboards\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Badge;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuGroup;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->getFooter();

        Nova::mainMenu(function (Request $request, Menu $menu) {
            return [
                MenuSection::make('Main', [
                    MenuItem::dashboard(Features::class),
                ])->icon('chart-bar')->collapsable(),
                MenuSection::make('Features', [
                    MenuItem::make('Admin Areas', 'resources/admin-areas'),
                    MenuItem::make('Places', 'resources/places'),
                    MenuItem::make('Hiking Routes', 'resources/hiking-routes'),
                    MenuItem::make('Poles', 'resources/poles'),
                    MenuGroup::make('Admin', [])->collapsable(),
                ])->icon('globe')->collapsable(),
                MenuSection::make('Admin', [
                    MenuItem::make('Users', 'users'),
                    //create a link menu item
                    MenuItem::make('API', url('/api/documentation'))->external()->openInNewTab(),
                ])->icon('users')->collapsable(),
                MenuSection::make('Tools', [
                    MenuItem::externalLink('Display Jobs', url('/jobs'))->withBadgeIf(Badge::make('Some jobs failed', 'warning'), 'warning', fn () => DB::table('queue_monitor')->where('status', 2)->count() > 0)->openInNewTab(),

                ])->icon('briefcase'),
            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'team@webmapp.it',
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new Features,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Nova::$initialPath = '/dashboards/features';
    }

    //create a footer
    private function getFooter()
    {
        Nova::footer(function () {
            return Blade::render('nova/footer');
        });
    }
}
