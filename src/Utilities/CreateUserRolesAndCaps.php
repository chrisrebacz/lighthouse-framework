<?php
namespace Lighthouse\Utilities;

use Lighthouse\Support\Traits\ArrayedFileLoader;

class CreateUserRolesAndCaps extends Utility
{
    use ArrayedFileLoader;

    /**
     * List of WP_Role objects
     * @var array
     */
    protected $wpRoles = [];

    /**
     * Execute the utility
     * @return void
     */
    public function run()
    {
        if (! empty($this->config)) {
            if (isset($this->config['caps'])) {
                $this->createCapabilities($this->config['caps']);
            }

            if (isset($this->config['roles'])) {
                $this->createRoles($this->config['roles']);
                if (isset($this->config['access'])) {
                    $this->buildUserRolePermissions($this->config['access']);
                }
            }
        }
    }

    /**
     * Create custom capabilities and assign them to the admin
     * role so that admin will always have relevant permissions.
     * @param  array  $caps 
     * @return void
     */
    public function createCapabilities($caps = [])
    {
        $admin = get_role('administrator');
        foreach ($caps as $cap) {
            if (! $admin->has_cap($cap)) {
                $admin->add_cap($cap);    
            }
        }
    }

    /**
     * When assigning capabilities when creating a user role, you
     * need to pass an array with the cap as key and a boolean
     * value. If the caps array is index-based, this will 
     * mess up the user role permissions. 
     * @param  array  $caps  
     * @param  boolean $allow
     * @return array
     */
    public function formatCapabilities($caps, $allow = true)
    {
        $arr = [];
        foreach ($caps as $cap) {
            $arr[$cap] = $allow;
        }
        return $arr;
    }

    /**
     * Add capabilities to an existing WordPress role
     * @param WP_Role $role
     * @param array $caps
     * @return void
     */
    public function addCapsToRole($role, $caps)
    {
        $role = get_role($role);
        foreach ($caps as $cap) {
            if (! $role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Create a role within WordPress
     * @param  string $role Slug name of the role
     * @param  array $data  Array containing label, whether to clone an existing role's capabilities, and custom caps to add.
     * @return void
     */
    protected function createRole($role, $data)
    {
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        if (isset($data['clone'])) {
            $auth = $wp_roles->get_role($data['clone']);
            if ($auth) {
                $wp_roles->add_role($role, $data['label'], $auth->capabilities);
                $this->addCapsToRole($role, $data['caps']);
            } 
        } else {
            $caps = $this->formatCapabilities($data['caps']);
            $wp_roles->add_role($role, $data['label'], $caps);
        }   
    }

    /**
     * Create an array of user roles
     * @param  array $roles 
     * @return void
     */
    protected function createRoles($roles)
    {
        foreach ($roles as $name => $data) {
            if (! lighthouse_user_role_exists($name)) {
                $this->createRole($name, $data);
            } else {
                if (isset($data['caps'])) {
                    $caps = $this->formatCapabilities($data['caps']);
                    $this->addCapsToRole($name, $caps);    
                }
            }
        }
    }

    /**
     * Remove capabilities from all users
     * @param  array  $caps
     * @return void
     */
    public function removeCaps($caps = [])
    {
        global $wp_roles;
        foreach ($caps as $cap) {
            foreach (array_keys($wp_roles->roles) as $role) {
                $cap = (string) $cap;
                $wp_roles->remove_cap($role, $cap);
            }
        }
    }

    public function buildUserRolePermissions($access)
    {
        $this->createAccessCategories($access['terms'], 0, $access['taxonomy']);
    }

    /**
     * Create taxonomy terms for access/content permissions
     * @param  array  $terms
     * @param  integer $term_id
     * @return void
     */
    protected function createAccessCategories($terms, $term_id = 0, $taxonomy = 'category')
    {
        if (file_exists (ABSPATH.'/wp-admin/includes/taxonomy.php')) {
            require_once (ABSPATH.'/wp-admin/includes/taxonomy.php'); 
            lighthouse_create_access_category($terms, $term_id, $taxonomy);
        }
    }
}