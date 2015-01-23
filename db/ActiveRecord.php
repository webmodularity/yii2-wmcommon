<?php

namespace wmc\db;

use yii\base\InvalidConfigException;
use yii\db\IntegrityException;

class ActiveRecord extends \yii\db\ActiveRecord
{

    /**
     * Takes a full AR model with an unset PK and returns AR result if record is found
     * @return ActiveRecord|null null if no result found
     */

    public function findOneFromAttributes() {
        return $this->findOne($this->getAttributes(null, $this->primaryKey()));
    }

    /**
     * @param $condition
     * @param bool $runValidation
     * @param null $attributes
     * @return $this|static
     * @throws \Exception
     * @throws \yii\db\IntegrityException on failed insert
     */

    public function findOneOrInsert($condition, $runValidation = true, $attributes = null) {
        $find = static::findOne($condition);
        if (is_null($find)) {
            if ($this->insert($runValidation, $attributes)) {
                return $this;
            } else {
                throw new \yii\db\IntegrityException("Failed to insert ".static::className()." record!");
            }
        }
        return $find;
    }

    /**
     * ONLY WORKS WITH INNODB TABLES CURRENTLY
     * Merges $oldModel into $newModel by first finding all tables with a FK pointing to this table,
     * updating those tables to point to $newModel, then deleting $oldModel record from database.
     * ONLY WORKS WITH INNODB TABLES CURRENTLY
     * @param $oldModel ActiveRecord The model that will be merged with $newModel and deleted
     * @param $newModel ActiveRecord The model that will survive
     * @throws InvalidConfigException
     * @return int number of rows updated in target tables
     */

    public static function mergeRedundantRecord($oldModel, $newModel) {
        $db = static::getDb();
        $rows = 0;
        if ($oldModel->className() != $newModel->className()) {
            throw new InvalidConfigException("You can only merge ActiveRecords of the same class!");
        }
        $mergeTableName = $oldModel->tableSchema->fullName;
        $mergeTablePkArray = $oldModel->getTableSchema()->primaryKey;
        $mergeTablePk = array_shift($mergeTablePkArray);
        $dbName = $db->createCommand("SELECT DATABASE() as DBNAME;")->queryOne();
        $dependentTables = $db->createCommand("SELECT TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
                                   WHERE CONSTRAINT_SCHEMA = :dbName AND REFERENCED_TABLE_NAME = :tableName")
            ->bindValues([':dbName' => $dbName['DBNAME'], ':tableName' => $mergeTableName])
            ->queryAll();

        foreach ($dependentTables as $dependentTable) {
            $dependentFks = $db->schema->getTableSchema($dependentTable['TABLE_NAME'])->foreignKeys;
            foreach ($dependentFks as $dependentFk) {
                $dependentFkTable = array_shift($dependentFk);
                if ($dependentFkTable == $mergeTableName) {
                    // Find FK that points to $mergeTablePk
                    foreach ($dependentFk as $dependentTableCol => $targetPk) {
                        if ($targetPk == $mergeTablePk) {
                            $updateCount = $db->createCommand()
                                ->update(
                                    $dependentTable['TABLE_NAME'],
                                    [$dependentTableCol => $newModel->$mergeTablePk],
                                    [$dependentTableCol => $oldModel->$mergeTablePk]
                                )
                                ->execute();
                            $rows = $rows + $updateCount;
                        }
                    }
                }
            }
        }

        // Delete $oldModel
        return $oldModel->delete();
    }

    public function link($name, $model, $extraColumns = []) {
        try {
            parent::link($name, $model, $extraColumns);
        } catch (IntegrityException $e) {
            // Duplicate record, does it really matter?
        }
    }
}