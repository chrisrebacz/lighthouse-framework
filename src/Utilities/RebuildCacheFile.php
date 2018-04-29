<?php
namespace Lighthouse\Utilities;

class RebuildCacheFile 
{
    public function __construct()
    {
        $this->items = app('config')->all();
    }

    public function rebuild()
    {
        try{
            $path = app()->getCachedConfigPath();
            $items = app('config')->all();
            $filesystem = app('files');
            $filesystem->delete($path);
            $filesystem->put(
                $path,
                '<?php return '.var_export($items, true).';'.PHP_EOL
            );
            return [
                'message' => 'Booyah!',
                'text' => 'The cached config file was rebuilt so config options can be current',
                'type' => 'success'
            ];      
        } catch (Exception $e) {
            return [
                'message' => 'Something went wrong',
                'text' => $e->getMessage(),
                'type'  => 'error'
            ];
        }
    }
}