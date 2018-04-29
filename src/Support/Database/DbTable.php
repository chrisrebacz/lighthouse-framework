<?php
namespace Lighthouse\Support\Database;

abstract class DbTable
{
    /** @var string WordPress and App Prefix */
    protected $prefix;

    /** @var string Base table name without prefix */
    public $table;

    public function __construct($baseTableName)
    {
        global $wpdb;
        $this->prefix = $wpdb->prefix;
        $this->table = $baseTableName;
    }

    /**
     * The method that is called when the plugin is initially activated.
     * This should focus on creating one or more related custom tables.
     * @return void
     */
    abstract public function up();

    /**
     * When deleting the plugin, we should clean up the database tables we
     * created. This function allows us to do that. Currently, we are not
     * actually running this method because we don't want to delete data
     * accidentally and since we don't have an easy way to update the plugin.
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists($this->getTableName());
    }

    /**
     * Get the actual table name. This would include both the prefix
     * and the base table name.
     * @return string
     */
    public function getTableName()
    {
        return $this->prefix . $this->table;
    }
}