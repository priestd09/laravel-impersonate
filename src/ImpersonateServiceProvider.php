<?php

namespace Lab404\Impersonate;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Lab404\Impersonate\Middleware\ProtectFromImpersonation;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * Class ServiceProvider
 *
 * @package Lab404\Impersonate
 */
class ImpersonateServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @var string
     */
    protected $configName = 'laravel-impersonate';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();

        $this->app->bind(ImpersonateManager::class, ImpersonateManager::class);

        $this->app->singleton(ImpersonateManager::class, function ($app)
        {
            return new ImpersonateManager($app);
        });

        $this->app->alias(ImpersonateManager::class, 'impersonate');

        $this->registerRoutesMacro();
        $this->registerBladeDirectives();
        $this->registerMiddleware();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }

    /**
     * Register plugin blade directives.
     *
     * @param   void
     * @return  void
     */
    protected function registerBladeDirectives()
    {
        Blade::directive('impersonating', function() {
            return '<?php if (app()["auth"]->check() && app()["auth"]->user()->isImpersonated()): ?>';
        });

        Blade::directive('endImpersonating', function() {
            return '<?php endif; ?>';
        });

        Blade::directive('canImpersonate', function() {
            return '<?php if (app()["auth"]->check() && app()["auth"]->user()->canImpersonate()): ?>';
        });

        Blade::directive('endCanImpersonate', function() {
            return '<?php endif; ?>';
        });
    }

    /**
     * Register routes macro.
     *
     * @param   void
     * @return  void
     */
    protected function registerRoutesMacro()
    {
        $router = $this->app['router'];

        $router->macro('impersonate', function () use ($router) {
            $router->get('/impersonate/take/{id}', '\Lab404\Impersonate\Controllers\ImpersonateController@take')->name('impersonate');
            $router->get('/impersonate/leave', '\Lab404\Impersonate\Controllers\ImpersonateController@leave')->name('impersonate.leave');
        });
    }

    /**
     * Register plugin middleware.
     *
     * @param   void
     * @return  void
     */
    public function registerMiddleware()
    {
        $this->app['router']->aliasMiddleware('impersonate.protect', ProtectFromImpersonation::class);
    }

    /**
     * Merge config file.
     *
     * @param   void
     * @return  void
     */
    protected function mergeConfig()
    {
        $configPath = __DIR__ . '/../config/' . $this->configName . '.php';

        $this->mergeConfigFrom($configPath, $this->configName);
    }

    /**
     * Publish config file.
     *
     * @param   void
     * @return  void
     */
    protected function publishConfig()
    {
        $configPath = __DIR__ . '/../config/' . $this->configName . '.php';

        $this->publishes([$configPath => config_path($this->configName . '.php')], 'impersonate');
    }
}