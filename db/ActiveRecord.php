<?php

namespace wmc\db;

use yii\base\InvalidConfigException;
use yii\db\IntegrityException;
use yii\helpers\Inflector;

class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Datetime strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date \DateTime|integer|null Datetime or timestamp to format
     * @return string Datetime string suitable for insert into MySQL DATETIME or TIMESTAMP column
     */

    public static function getMysqlDatetime($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof \DateTime ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $date);
    }

    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Date strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date \DateTime|integer|null Datetime or timestamp to format
     * @return string Date string suitable for insert into MySQL DATE column
     */

    public static function getMysqlDate($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof \DateTime ? $date->format('Y-m-d') : date('Y-m-d', $date);
    }

    /**
     * This function converts a human readable IPv4 or IPv6 address into an address family appropriate
     * 32bit or 128bit binary structure. Suitable for MySQL VARBINARY(16) columns.
     * @param $ip A human readable IPv4 or IPv6 address.
     * @return null|string Returns the in_addr representation of the given ip or NULL if ip is invalid
     */

    public static function getBinaryIp($ip) {
        $inetPton = inet_pton($ip);
        return $inetPton === false ? null : $inetPton;
    }

    public static function getReadableConstantList($prefix = '', $key = null) {
        $reflection = new \ReflectionClass(static::className());
        $constants = $reflection->getConstants();
        $constantList = [];
        foreach ($constants as $cName => $cVal) {
            if (!empty($prefix)) {
                if (substr($cName, 0, strlen($prefix)) != $prefix) {
                    continue;
                }
                $humanized = Inflector::humanize(substr($cName, (strlen($prefix) - 1)));
                if (!empty($key)) {
                    if ($key == $cVal) {
                        return $humanized;
                    }
                } else {
                    $constantList[$cVal] = $humanized;
                }
            }
        }
        return !empty($key) ? null : $constantList;
    }

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

    public function findOneOrInsert($condition = null, $runValidation = true, $attributes = null) {
        $find = is_null($condition) ? $this->findOneFromAttributes() : static::findOne($condition);
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
/*
    public function link($name, $model, $extraColumns = []) {
        try {
            parent::link($name, $model, $extraColumns);
        } catch (IntegrityException $e) {
            // Duplicate record, does it really matter?
        }
    }
 */
}