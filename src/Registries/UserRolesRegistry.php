<?php
namespace Lighthouse\Registries;

class UserRolesRegistry 
{
    protected $roles = [];

    public function add($role)
    {
        //add a new user role
    }

    public function addCapability($cap, $roles = [])
    {
        //add a capability to roles.  if none 
        //are passed, add only to admin.  
    }

    public function seed()
    {
        
    }

    public function getRoles()
    {
        //get the list of roles from the config
    }

    public function bindWpRoles()
    {
        global $wp_roles;
        $this->wpRoles = $wp_roles;
    }

    public function getWpRoles()
    {
        if (empty($this->wpRoles)) {
            $this->bindWpRoles();
        }
        return $this->wpRoles;
    }
}