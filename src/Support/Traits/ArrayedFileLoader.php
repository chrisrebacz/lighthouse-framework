<?php 
namespace Lighthouse\Support\Traits;

/*
|----------------------------------------------------------------
| Trait to load file data into an array
|----------------------------------------------------------------
| From configuration to messaging, a lot of key data is stored in
| files that return an array. This trait simplifies the process
| of getting that arrayable data into a singular object that
| can be compiled into a single cache file for production.
| 
*/
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Lighthouse\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

trait ArrayedFileLoader
{
    /**
     * The specific key-value pairs that are contained within
     * the arrayed files.
     * @var array
     */
    protected $items = [];

    /**
     * Loads all of the files within a given directory into
     * the items array
     * @param  string $directory 
     * @return void        
     */
    public function loadDirectoryFiles($directory)
    {
        $files = $this->getDirectoryFiles($directory);
        foreach ($files as $key => $path) {
            $this->setData($key, $path);
        }
    }

    /**
     * Sets the data within the main configuration object
     * @param string $key  
     * @param string $path
     */
    protected function setData($key, $path)
    {
        if (method_exists($this, 'set') &&
            in_array('ArrayAccess', class_implements($this))
        ) {
            $this->set($key, require $path);
        }
    }

    /**
     * Finds all of the files within a directory
     * @param  string $directory 
     * @return array 
     */
    protected function getDirectoryFiles($directory)
    {
        $files = [];
        foreach (Finder::create()->files()->name('*.php')->in($directory) as $file) {
            $nesting = $this->getDirectoryNesting($file, $directory);
            $files[$nesting.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }
        return $files;
    }

    /**
     * Recursively reviews any sub-directories for files.
     * @param  SplFileInfo $file      
     * @param  string      $directory 
     * @return array              
     */
    protected function getDirectoryNesting(SplFileInfo $file, $directory)
    {
        $nested_directory = dirname($file->getRealPath());
        if ($tree = trim(str_replace($directory, '', $nested_directory), DIRECTORY_SEPARATOR)) {
            $tree = str_replace(DIRECTORY_SEPARATOR, '.', $tree).'.';
        }

        return $tree;
    }

    /**
     * Compiles the arrayed items into a single cache file
     * @param  string $path    
     * @param  array $options 
     * @return void  
     */
    public function compileCacheFile($path, $options)
    {
        if (! property_exists($this, 'filesystem')) {
            $this->filesystem = new Filesystem;
        }
        $this->filesystem->put($path, '<?php return '.var_export($options, true).';'.PHP_EOL);
    }

    /**
     * Gets the arrayed file data
     * @return array
     */
    public function getFileData()
    {
        return $this->items;
    }

    /**
     * Merge a plugin or services data within the parent Lighthouse
     * framework config data object
     * @param  array $data 
     * @param  string $type 
     * @return void
     */
    public function mergeWithAppData($data, $type = 'config')
    {
        if (! in_array($type, ['config', 'lang', 'options'])) {
            throw new InvalidArgumentException('The data you wish to merge is not supported');
        }
        $parent = $this->app[$type];
        foreach ($data as $key => $items) {
            $current = $parent->get($key, []);
            $parent->set($key, array_merge($items, $current));
        }
    }

    public function confirmDirectoryExists($path)
    {
        return wp_mkdir_p($path);
    }

    /**
     * Writes the data to a php file.
     * @param  array $items 
     * @param  string $path  
     * @return void
     */
    public function compile($items = null, $path)
    {
        app('files')->put(
            $path,
            '<?php return '.var_export($items, true).';'.PHP_EOL
        );
    }
}
