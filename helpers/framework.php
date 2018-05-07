<?php 
/*
|----------------------------------------------------------------
| Framework Helpers
|----------------------------------------------------------------
| These wrapper functions that make it easier to work within the
| application. For example, you have an app() function to access
| the Application Container or a view() function to render
| a blade template.  
 */

use Lighthouse\Container;
use Illuminate\Contracts\Support\Htmlable;

if (!function_exists('app')) {
    /**
     * Helper to access the Lighthouse IOC container
     */
    function app($binding = null)
    {
        $instance = Container::getInstance();
        if (is_null($binding)) {
            return $instance;
        }
        return $instance[$binding];
    }
}

if (!function_exists('lighthouse')) {
    /**
     * Helper to access the Lighthouse IOC container
     */
    function lighthouse($binding = null)
    {
        $instance = Container::getInstance();
        if (is_null($binding)) {
            return $instance;
        }
        return $instance[$binding];
    }
}

if (!function_exists('config')) {
    /**
     * Get/set a configuration value. If an array is passed as the key
     * we will assume you want to set an array of values.
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('dd')) {
    /**
     * Die and dump 
     * This builds on the Symfony Var Dumper Class
     */
    function dd()
    {
        $obj = func_get_args();
        call_user_func_array('dump', $obj);
        die;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
     * @return string
     */
    function e($value)
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}


if (!function_exists('env')) {
    /**
     * Helper to get environment variables
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (strlen($value) > 1 && starts_with($value, '"') && ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('view')) {
    function view($name, $context = [], $mergeData = [], $echo = true)
    {
        $view = app('view')->make($name, $context, $mergeData)->render(); 

        if (!$echo) {
            return $view;
        }
        echo $view;
    }
}


if (!function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed  $object
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('writeLog')) {
    function writeLog($log)
    {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

if (! function_exists('lighthouse_user_role_exists')) {
    function lighthouse_user_role_exists($role) {
        if (!empty($role)) {
            return $GLOBALS['wp_roles']->is_role($role);
        }
        return false;
    }
}
