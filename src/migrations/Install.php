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

use Craft;
use craft\db\Migration;
use thepixelage\discountsplus\db\Table;

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
        $this->createIndex(null, Table::DISCOUNTS, ['id']);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(),
            Table::DISCOUNTS,
            'id',
            '{{%commerce_discounts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }


    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists(Table::DISCOUNTS);
    }
}
