<?php 
namespace Lighthouse;

use Exception;
use Illuminate\Events\Dispatcher;
use Lighthouse\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider; 
use Lighthouse\Security\BcryptPasswordHasher;
use Illuminate\Database\Capsule\Manager as Database;

class FrameworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        try {
            $this->registerEvents();
            $this->registerEloquentOrm();
            $this->registerFilesystem();
        } catch (Exception $e) {
            if (function_exists('is_admin') && is_admin()) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><h3 style="margin-bottom: 0">Issue registering Lighthouse</h3><p><?php echo $e->getMessage(); ?></p></div>';
                });
            }
        }
    }

    /**
     * Actions to take when the FrameworkServiceProvider is booted.
     */
    public function boot()
    {        
        new BcryptPasswordHasher();
    }

    /**
     * Dependency of using Eloquent or Filesystem. 
     * @return void 
     */
    protected function registerEvents()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make('Illuminate\Contracts\Queue\Factory');
            });
        });
    }

    /**
     * Register the Eloquent capsule manager within the container
     * @return void
     */
    public function registerEloquentOrm()
    {
        global $wpdb;
        $capsule = new Database($this->app);

        $collation = 'utf8_general_ci';
        if (defined('DB_CHARSET') && DB_CHARSET == 'utf8mb4') {
            $collation = 'utf8mb4_general_ci';
        }

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'collation' => DB_COLLATE ? : $collation,
            'prefix' => $wpdb->prefix
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->app->instance('db', $this->app->make('Illuminate\Database\Capsule\Manager', ['app' => $this->app]));
        $this->app->alias('db', 'Illuminate\Database\Capsule\Manager');
    }

    public function registerFilesystem()
    {
        $filesystem = new Filesystem($this->app);
        $this->app->instance('files', $filesystem);
        $this->app->alias('files', 'Lighthouse\Filesystem\Filesystem');
    }
}
