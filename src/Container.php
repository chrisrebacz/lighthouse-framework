<?php 
namespace Lighthouse;

use Exception;
use ErrorException;
use Illuminate\Support\ServiceProvider;
use Lighthouse\Settings\DatabaseOptions;
use Lighthouse\FrameworkServiceProvider;
use Lighthouse\Settings\ConfigFiles as Config;
use Illuminate\Container\Container as IlluminateContainer;

class Container extends IlluminateContainer
{
    /**
     * The Container instance singleton
     * @var \Lighthouse\Container
     */
    protected static $instance;


    /**
     * Whether the Lighthouse framework has been bootstrapped
     * @var boolean
     */
    protected $hasBeenBootstrapped = false;

    /**
     * Whether the Lighthouse plugin has been booted, i.e.,
     * fully set up. 
     * @var boolean
     */
    protected $booted = false;

    /**
     * All of the registered service providers
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers
     * @var array
     */
    public $loadedProviders = [];

    /**
     * Whether the plugin is network activated
     * on a WordPress multisite.
     */
    public $onNetwork = false;

    /**
     * The mu-plugin init file, or if loaded
     * within a plugin, the plugin base file.
     */
    public $baseName;

    /**
     * Whether the View Service Provider has been 
     * registered, enabling the use of blade templates. 
     */
    public $viewsRegistered = false;


    /**
     * Instantiate the Lighthouse Container
     * @return Lighthouse\Container
     */
    public function __construct($baseName)
    {
        $this->setEnvironment();
        $this->bootstrapContainer();
    }

    /**
     * Identify the environment that you are working in. The two core 
     * options are 'production' and 'development'.  We also want to be
     * sure we capture whether the application's request derives from
     * the command line rather than http. 
     */
    protected function setEnvironment()
    {
        if (defined('WP_ENV')) {
            $environment = WP_ENV;
        } else {
            $environment = defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production';
        }

        $this->instance('env', $environment);

        if (!defined('RUNNING_IN_CONSOLE')) {
            define('RUNNING_IN_CONSOLE', php_sapi_name() == 'cli');
        }
        $this->onNetwork = $this->isNetwork();
    }

    public function isNetwork()
    {
        $plugins = get_site_option('active_sitewide_plugins');
        
        if (is_multisite() && isset($plugins[$this->baseName])) {
            return true;
        }

        return false;
    }


    /**
     * Since this class extends the Illuminate container, we want to
     * make sure our instance is resolved whenever a class/object
     * requires an instance of the IOC container or 'app'.
     * @return void
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('Illuminate\Container\Container', $this);
    }

    /**
     * Set up the core services included in the framework so that
     * they can be made available in plugins or the theme.
     * @return void
     */
    public function run()
    {
        $this->register(new FrameworkServiceProvider($this));
        $this->registerContainerAliases();

        $this->enableSettings();

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Set up the Config and Options instance within the 
     * Container.  Config allows us to utilize file-based
     * configuration; Options focuses on settings saved in 
     * the database within the appropriate options setting.
     * @return void
     */
    protected function enableSettings()
    {
        $this->initializeConfig();
        $this->initializeOptions();
    }


    protected function initializeConfig()
    {
        // if we are booting Eloquent, it will add db connection 
        // details as a fluent collection. We don't want to 
        // overwrite those with our own items so we need to 
        // check if something is already registered within the 
        // container.
        if ($this->bound('config')) {
            $items = $this['config'];
            $items = is_array($items) ? $items : $items->toArray();
        } else {
            $items = [];
        }

        $config = new Config($items);
        
        $this->instance('config', $config);
    }


    protected function initializeOptions()
    {
        if ($this->isNetwork()) {
            $items = get_site_option('lighthouse_options', []);
        } else {
            $items = get_option('lighthouse_options', []);
        }
        $options = new DatabaseOptions($items, 'lighthouse_options');

        $this->instance('options', $options);
    }

    /**
     * Register aliases within the container for framework
     * services, so that we can easily inject dependencies
     * as needed throughout the application. 
     * @return void
     */
    public function registerContainerAliases()
    {
        $aliases = [
            'app' => [
                'Lighthouse\Container',
                'Illuminate\Contracts\Container\Container'
            ],
            'config' => [
                'Lighthouse\Settings\ConfigFiles',
                'Illuminate\Contracts\Config\Repository'
            ],
            'db.connection' => [
                'Illuminate\Database\Connection',
                'Illuminate\Database\ConnectionInterface'
            ],
            'events' => [
                'Illuminate\Events\Dispatcher',
                'Illuminate\Contracts\Events\Dispatcher'
            ],
            'view' => [
                'Lighthouse\View\Factory',
                'Illuminate\Contracts\View\Factory'
            ],
            'blade.compiler' => 'Lighthouse\View\Compilers\BladeCompiler',
            'db' => ['Illuminate\Database\Capsule\Manager'],
            'files' => ['Lighthouse\Filesystem\Filesystem']
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array)$aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Register a service provider within the Container.
     * @param Illuminate\Support\ServiceProvider|string $provider
     * @param array $options
     * @param boolean $force
     * @return Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = array(), $force = false)
    {
        if ($registered = $this->getProvider($provider) && !$force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve 
        // it, passing in the application instance automatically 
        // for the developer. This is simply a more convenient 
        // way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }
        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call 
        // this boot method on the provider class so it has an 
        // opportunity to do its boot logic and will be ready 
        // for any usage by the developer's application logics.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        return array_first(
            $this->serviceProviders,
            function ($key, $value) use ($name) {
                return $value instanceof $name;
            }
        );
    }

    /**
     * Resolve a service provider instance from the class name.
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     * @param  \Illuminate\Support\ServiceProvider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    /**
     * Register all of the configured providers. These would 
     * have been added to the config/app.php file within the 
     * providers array or within the modules array.  
     * @return void
     */
    public function registerConfigurableProviders()
    {
        $providers = $this['config']->get('app.providers', []);
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Boot the service providers (i.e., call their respective 
     * boot methods)
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        // Once the application has booted we will also fire 
        // some "booted" callbacks for any listeners that need 
        // to do work after this initial booting gets finished. 
        // This is useful when ordering the boot-up processes we
        // run.
        if (function_exists('do_action')) {
            do_action('flow_booting');
        }
        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });
        $this->booted = true;
        if (function_exists('do_action')) {
            do_action('flow_booted');
        }
    }

    /**
     * Boot the given service provider.
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Check whether flow has already gone through the 
     * booting process. This is necessary for any services we 
     * only want loaded in specific circumstances (e.g., Excel 
     * export services)
     * @return boolean
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Check whether flow has already gone through the 
     * registration of providers and modules process.  
     * @return boolean 
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Get the providers which have been registered and loaded 
     * for use in the application
     * @return array 
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }


    /**
     * Clear the container of all bindings
     * @return void
     */
    public function flush()
    {
        parent::flush();
        $this->buildStack = [];
        $this->loadedProviders = [];
        $this->serviceProviders = [];
    }


    /**
     * Get the container instance.
     * @return \Flow\Container
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * There should be a lighthouse directory in uploads
     * that can be used by plugins. This is a path to the
     * directory. 
     * @param string|null $path
     * @return string
     */
    public function storagePath($path = null)
    {
        $storagePath = null;
        $default = wp_upload_dir()['basedir'];
        if (defined('LH_UPLOADS')) {
            $storagePath = rtrim(LH_UPLOADS, '/') . '/lighthouse';
        }
        if (is_null($storagePath) || !file_exists($storagePath)) {
            $storagePath = $default;
        }

        if ($path !== null) {
            return $storagePath . '/' . rtrim($path, '/');
        }

        return $storagePath;
    }

    /**
     * Get the path to the directory where compiled views 
     * are stored
     * @return string
     */
    public function getCachedViewsPath()
    {
        return $this->storagePath() . '/views';
    }

    /**
     * Check whether WP CRON is being used
     * @return boolean
     */
    public function isCron()
    {
        return defined('DOING_CRON') && DOING_CRON;
    }

    /**
     * Check whether this is an ajax call to admin_ajax.
     * @return boolean
     */
    public function isAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Determine whether the application is running in the 
     * console
     * @return boolean
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Determine whether we are running unit tests or if this is 
     * a real request
     * @return boolean
     */
    public function runningUnitTests()
    {
        return defined('LIGHTHOUSE_TEST_ENV') && LIGHTHOUSE_TEST_ENV;
    }

    /**
     * Identify the version of the application that we are 
     * running.
     * @return string
     */
    public function version()
    {
        return LH_VERSION;
    }

    /**
     * Get or check the current application environment.
     * @param  mixed
     * @return string
     */
    public function environment()
    {
        if (func_num_args() > 0) {
            $patterns = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();
            foreach ($patterns as $pattern) {
                if (str_is($pattern, $this['env'])) {
                    return true;
                }
            }
            return false;
        }

        return $this['env'];
    }

    /**
     * Determine whether the application is currently in 
     * maintenance mode.
     * @return boolean
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Bind directory paths into the container for use 
     * in the application.
     * @param array $paths
     * @return void
     */
    public function bindPathsInContainer($paths = [])
    {
        if (! is_array($paths)) {
            return;
        }    

        foreach($paths as $key => $path) {
            $this->instance('path.'.$key, $path);
        }
    }

    /**
     * Bind a set of URIs into the container for use within
     * the plugin.
     * @param array $uris
     * @return void
     */
    public function bindUrisInContainer($uris = [])
    {
        if (! is_array($uris)) {
            return;
        }

        foreach($uris as $key => $uri) {
            $this->instance('uri.'.$key, $uri);
        }
    }
}
