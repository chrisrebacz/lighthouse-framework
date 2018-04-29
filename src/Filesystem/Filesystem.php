<?php
namespace Lighthouse\Filesystem;

/**
 * BASED ON ILLUMINATE/FILESYSTEM/FILESYSTEM
 * By Taylor Otwell
 */


use ErrorException;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Traits\Macroable;
use Lighthouse\Exceptions\FileNotFoundException;

class Filesystem
{
    use Macroable;

    /**
     * Determine if a file exists
     * @param  string $path 
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Get the contents of a file
     * @param  string $path 
     * @return string|bool  
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }
        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Get contents of a file with shared access
     * @param  string $path 
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';
        $handle = fopen($path, 'rb');
        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);
                    $contents = fread($handle, $this->size($path) ?: 1);
                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Require a file
     * @param  string $path 
     * @return string|bool 
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $path;
        }
        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Require the given file once
     * @param  string $file 
     * @return mixed 
     */
    public function requireOnce($file)
    {
        require_once $file;
    }

    /**
     * Get the MD5 hash of the file at the given path
     * @param  string $path
     * @return string
     */
    public function hash($path)
    {
        return md5_file($path);
    }

    /**
     * Put contents into a file
     * @param  string  $path     
     * @param  string  $contents 
     * @param  boolean $lock   
     * @return string  
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Prepend to a file
     * @param  string $path
     * @param  string $data
     * @return int
     */
    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data.$this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file
     * @param  string $path
     * @param  string $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     * @param  string  $path
     * @param  int  $mode
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete files at a given path
     * @param  string|array $paths
     * @return bool
     */
    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();
        $success = true;
        foreach ($paths as $path) {
            try {
                if (! @unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Move a file to a new location
     * @param  string $path
     * @param  string $target
     * @return bool  
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location
     * @param  string $path
     * @param  string $target
     * @return bool 
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }

    /**
     * Create a hard link to the target file or directory
     * @param  string $target 
     * @param  string $link   
     * @return void
     */
    public function link($target, $link)
    {
        if (! windows_os()) {
            return symlink($target, $link);
        }
        $mode = $this->isDirectory($target) ? 'J' : 'H';
        exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
    }

    /**
     * Extract the file name from a file path
     * @param  string $path
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path
     * @param  string $path 
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path
     * @param  string $path
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path
     * @param  string $path
     * @return string   
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Return the type of file 
     * @param  string $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file
     * @param  string $path
     * @return string|false 
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Return the size of a given file
     * @param  string $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * Return the last modified timestamp for a file
     * @param  string $path 
     * @return string   
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }

    /**
     * Check whether a path is pointing to a directory
     * @param  string  $directory
     * @return boolean 
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Check whether a path is readable
     * @param  string  $path
     * @return boolean 
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Check whether a path is writable
     * @param  string  $path
     * @return boolean 
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * Check whether a path is a file
     * @param  string  $file
     * @return boolean 
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Glob a directory
     * @param  string  $pattern
     * @param  integer $flags  
     * @return string
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * List the files within a directory
     * @param  string $directory 
     * @return array
     */
    public function files($directory)
    {
        $glob = glob($directory.DIRECTORY_SEPARATOR.'*');
        if ($glob === false) {
            return [];
        }

        return array_filter($glob, function ($file) {
            return filetype($file) == 'file';
        });
    }

    /**
     * Get all files within a directory
     * @param  string $directory
     * @return array      
     */
    public function allFiles($directory, $hidden = false)
    {
        return iterator_to_array(Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory), false);
    }

    /**
     * Get all directories in directory
     * @param  string $directory
     * @return array         
     */
    public function directories($directory)
    {
        $directories = [];
        foreach (Finder::create()->in($directory)->directories()->depth(0) as $dir) {
            $directories[] = $dir->getPathname();
        }
        return $directories;
    }

    /**
     * Create a directory
     * @param  string  $path      
     * @param  integer $mode      
     * @param  boolean $recursive 
     * @param  boolean $force     
     * @return boolean 
     */
    public function makeDirectory($path, $mode=0755, $recursive= false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $overwrite
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        if ($overwrite && $this->isDirectory($to)) {
            if (! $this->deleteDirectory($to)) {
                return false;
            }
        }
        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory in a new location
     * @param  string $directory   
     * @param  string $destination 
     * @param  array|null $options    
     * @return boolean 
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }
        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        if (! $this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }
        $items = new FilesystemIterator($directory, $options);
        foreach ($items as $item) {
            $target = $destination.'/'.$item->getBasename();
            if ($item->isDir()) {
                $path = $item->getPathname();
                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            } else {
                if (! $this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Delete a directory. Preserve param identifies whether to keep 
     * the folder and just delete the contents.
     * @param  string  $directory 
     * @param  boolean $preserve
     * @return boolean          
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);
        foreach ($items as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            } else {
                $this->delete($item->getPathname());
            }
        }
        if (! $preserve) {
            @rmdir($directory);
        }
        return true;
    }

    /**
     * Remove all contents from a directory
     * @param  string $directory
     * @return boolean
     */
    public function cleanDirectory($directory)
    {
        return $this->deleteDirectory($directory, true);
    }
}
