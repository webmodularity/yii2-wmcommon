<?php

use yii\db\Migration;

class m130524_201442_init_core extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Session
        if ($this->isMSSQL()) {
            $sessionDataType = 'BLOB';
        } else if ($this->isPostgres()) {
            $sessionDataType = 'BYTEA';
        } else {
            $sessionDataType = 'LONGBLOB';
        }
        $this->createTable('{{%session}}', [
            'id' => $this->char(64)->notNull(),
            'expire' => $this->integer()->defaultValue(NULL),
            'data' => $sessionDataType->defaultValue(NULL),
            'PRIMARY KEY (`id`)',
            'INDEX `index_expire` (`expire` ASC)'
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%session}}');
    }

    /**
     * @return bool
     */
    protected function isMSSQL()
    {
        return $this->db->driverName === 'mssql' || $this->db->driverName === 'sqlsrv' || $this->db->driverName === 'dblib';
    }

    /**
     * @return bool
     */
    protected function isPostgres()
    {
        return $this->db->driverName === 'pgsql';
    }
}
