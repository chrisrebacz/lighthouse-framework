<?php
namespace Lighthouse\Utilities;

use Lighthouse\Settings\DatabaseOptions;

class PublishOptions extends Utility
{
    /**
     * Execute the utility
     * @return void
     */
    public function run()
    {
        app('options')->save($this->key, $this->config);
    }
}