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

        if (empty($this->baseDir)) {
            $this->baseDir = app('path.uploads').'/lighthouse';
        }

        foreach ($this->config as $name) {
            if (function_exists('wp_mkdir_p')) {
                $path = $this->baseDir.'/'.$name;
                wp_mkdir_p($path);
            }
        }
    }
}


