<?php namespace Code16\Metrics;

use Code16\Metrics\Console\AnonymizeCommand;
use Code16\Metrics\Console\MigrateCommand;
use Code16\Metrics\Console\UpdateCommand;
use Illuminate\Support\ServiceProvider;
use Code16\Metrics\Middleware\MetricMiddleware;
use Code16\Metrics\Repositories\VisitRepository;
use Code16\Metrics\Repositories\MetricRepository;
use Code16\Metrics\Middleware\SetCookieMiddleware;
use Code16\Metrics\Middleware\NoTrackingMiddleware;
use Code16\Metrics\Middleware\StoreMetricMiddleware;
use Code16\Metrics\Repositories\Eloquent\VisitEloquentRepository;
use Code16\Metrics\Repositories\Eloquent\MetricEloquentRepository;
use Code16\Metrics\Listeners\LoginListener;
use Code16\Metrics\Listeners\LogoutListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * A Laravel 5's package template.
 *
 * @author: Rémi Collin 
 */
class MetricServiceProvider extends ServiceProvider {

    /**
     * This will be used to register config & view in 
     * your package namespace.
     */
    protected $packageName = 'metrics';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Publish your config
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path($this->packageName.'.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( __DIR__.'/../config/config.php', $this->packageName);

        $this->app->bind(MetricRepository::class, MetricEloquentRepository::class);
        $this->app->bind(VisitRepository::class, VisitEloquentRepository::class);

        $this->app->singleton(Manager::class, function($app) {
            return new Manager($app);
        });

        if($this->app['config']->get('metrics.enable')) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependMiddleware(MetricMiddleware::class);
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->pushMiddleware(StoreMetricMiddleware::class);
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->pushMiddleware(SetCookieMiddleware::class);
        }

        $router = $this->app['router'];
        $router->middleware('no_tracking', NoTrackingMiddleware::class);

        $this->registerListeners();

        $this->commands([
            UpdateCommand::class,
            MigrateCommand::class,
            AnonymizeCommand::class,
        ]);
    }

    protected function registerListeners()
    {
        $events = $this->app['events'];
        
        if($this->app['config']->get('metrics.enable')) {
            $events->listen(Login::class, LoginListener::class);
            $events->listen(Logout::class, LogoutListener::class);
        }
    }

}
