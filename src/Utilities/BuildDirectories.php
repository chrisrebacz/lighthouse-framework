<?php 
namespace Lighthouse\Utilities;

class BuildDirectories extends Utility
{
    public function run()
    {
        if (empty($this->config)) {
            //we can do one final check of the config collection.
            $this->config = app('config')->get('app.storage', []);
        }

        foreach ($this->config as $name) {
            if (function_exists('wp_mkdir_p')) {
                $path = app()->storagePath().'/'.$name;
                wp_mkdir_p($path);
            }
        }
    }
}


