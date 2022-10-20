<?php
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
    public string $driver;

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
    protected function createIndexes(): void
    {
        $this->createIndex(null, Table::DISCOUNTS, ['id']);
    }

    /**
     * @return void
     */
    protected function addForeignKeys(): void
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
    protected function removeTables(): void
    {
        $this->dropTableIfExists(Table::DISCOUNTS);
    }
}
