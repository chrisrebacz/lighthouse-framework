<?php
namespace Lighthouse\Utilities;

class RunCustomUtility extends Utility 
{
    /**
     * Execute the utility
     * @return void
     */
    public function run()
    {
        if (! is_array($this->config['classes'])) {
            $this->config['classes'] = [$this->config['classes']];
        }

        foreach ($this->config['classes'] as $customClass) {
            $utility = new $customClass($this->key);
            $utility->run();
        }
    }
}