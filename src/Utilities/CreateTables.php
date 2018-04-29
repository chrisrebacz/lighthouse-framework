<?php
namespace Lighthouse\Utilities;

use Illuminate\Database\Capsule\Manager as CapsuleManager;

class CreateTables extends Utility
{
    /**
     * Create the tables within the database only if they do not exist
     * @return mixed 
     */
    public function run()
    {
        if (empty($this->config)) {
            //one final check to see if we can find any tables to create.
            $this->config = app('config')->get('tables', []);
        }
        try {
            foreach ($this->config as $class => $name) {
                $tbl = new $class($name);
                
                if (CapsuleManager::schema()->hasTable($tbl->table)) {
                    continue;
                }
                $tbl->up();
            }    
        } catch (\Exception $e) {
            if (is_admin()) {
                return $e->getMessage();
            }
        }
        
        return true;
    }

    /**
     * Wrapper around the run method
     * @return boolean
     */
    protected function createCustomTables()
    {
        $this->run();
    }
}