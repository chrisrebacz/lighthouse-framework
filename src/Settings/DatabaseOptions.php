<?php 
namespace Lighthouse\Settings;

use Lighthouse\Support\Repository;

class DatabaseOptions extends Repository
{
    protected $key = 'lighthouse_options';

    /**
     * Save the Options array in the db.
     * @return boolean
     */
    public function update()
    {
        return update_option($this->key, $this->items);
    }

    /**
     * Helper to set the value in the repository and then
     * to update the db at the same time.
     * @param  string $key   
     * @param  mixed $items 
     * @return boolean
     */
    public function save($key, $items)
    {
        $this->set($key, $items);
        return $this->update();
    }

    /**
     * Get the options currently stored in db
     * @return array|boolean
     */
    public function fetchFromDb()
    {
        return get_option($this->key);
    }

    /**
     * Delete an option currently stored in db
     * @return boolean
     */
    public function delete()
    {
        return delete_option($this->key);
    }}