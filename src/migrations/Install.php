<?php
/**
 * Discounts Plus plugin for Craft CMS 3.x
 *
 * .
 *
 * @link      thepixelage.com
 * @copyright Copyright (c) 2022 thepixelage
 */

namespace thepixelage\discountsplus\migrations;

use thepixelage\discountsplus\db\Table;
use thepixelage\discountsplus\DiscountsPlus;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    thepixelage
 * @package   DiscountsPlus
 * @since     4.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables(): bool
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(Table::DISCOUNTS);
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                Table::DISCOUNTS,
                [
                    'id' => $this->primaryKey(),
                    'isLimitPerItemDiscountsMultiples' => $this->boolean()->defaultValue(false),
                    'limitDiscountsQuantity' => $this->integer()->null(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'dateDeleted' => $this->dateTime()->null(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%discountsplus_discountsplusrecord}}',
                'some_field',
                true
            ),
            '{{%discountsplus_discountsplusrecord}}',
            'some_field',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%discountsplus_discountsplusrecord}}', 'siteId'),
            '{{%discountsplus_discountsplusrecord}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%discountsplus_discountsplusrecord}}');
    }
}
