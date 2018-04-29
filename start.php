<?php 

if (! defined('LIGHTHOUSE_PLUGIN_BASENAME')) { 
    return; 
}

$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-warning"><h3 style="padding-bottom: 0px; margin-bottom: 0px;">Missing autoloader</h3><p style="margin-top">This plugin requires an autoloader to function properly. Make sure it is available or this plugin will do nothing.</p></div>';
    });
    
    return;
}
require $autoloader;


$lighthouse = new Lighthouse\Container(LIGHTHOUSE_PLUGIN_BASENAME);
$lighthouse->run();

