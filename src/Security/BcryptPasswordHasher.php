<?php
namespace Lighthouse\Security;

/*
|------------------------------------------------------------------------------
| Switch our password hashes to bcrypt
|------------------------------------------------------------------------------
| This class is credited to dxw from dxw.com who created the WpBcrypt plugin.
| We are essentially using it wholesale; however rather than have it loaded
| as a separate plugin, we are including it within YouKnow. 
| 
 */

class BcryptPasswordHasher
{
    public function __construct()
    {
        require_once(ABSPATH . 'wp-includes/class-phpass.php');
        global $wp_hasher;
        $wp_hasher = new \PasswordHash(10, false);
        add_filter('check_password', array($this, 'checkPassword'), 10, 4);
    }

    public function checkPassword($check = '', $password = '', $hash = '', $user_id = '')
    {
        if ($check && substr($hash, 0, 3) == '$P$') {
            wp_set_password($password, $user_id);
        }
        return $check;
    }
}