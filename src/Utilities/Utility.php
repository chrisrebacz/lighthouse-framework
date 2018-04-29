<?php
namespace Lighthouse\Utilities;

abstract class Utility 
{
    /**
     * Config item passed to utility
     * @var mixed
     */
    protected $config;

    /**
     * A meta key associated with the utility
     * @var string
     */
    protected $key;


    /**
     * Instantiate the utility
     * @param mixed $config
     * @param string $key
     */
    public function __construct($config, $key = 'lighthouse', $baseDir = null)
    {
        //need config to be an array
        if (! is_null($config) && is_string($config)) { 
            if (file_exists($config)) {
                //it is a file so let's require it.
                $config = require $config;
            } else {
                //it is just a string, so we will make it
                //a single item array.
                $config = [$config];
            }
        }
        $this->config = is_null($config) ? [] : $config;
        $this->key = $key;
        $this->baseDir = $baseDir;
    }

    /**
     * When extending Utility, make sure you include
     * a run method to start execution.
     * @return void
     */
    abstract function run();

}