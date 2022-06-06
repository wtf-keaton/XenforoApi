<?php

namespace CheatSite;

use XF\AddOn\AbstractSetup;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	public function install(array $stepParams = [])
	{
        $this->schemaManager()->createTable('cheats_user_hwid', function(Create $table) {
            $table->addColumn('id', 'int AUTO_INCREMENT');
            $table->addColumn('user_id', 'int');
            $table->addColumn('hwid', 'varchar', 256)->nullable();
            $table->addColumn('last_change_date', 'datetime')->nullable();
            $table->addPrimaryKey('id');
            $table->addKey('user_id');
        });

        $this->schemaManager()->createTable('cheats_log', function(Create $table) {
            $table->addColumn('id', 'int AUTO_INCREMENT');
            $table->addColumn('user_id', 'int');
            $table->addColumn('date', 'datetime');
            $table->addColumn('message', 'text');

            $table->addPrimaryKey('id');
            $table->addKey('user_id');
            $table->addKey('date');
        });

        $this->schemaManager()->createTable('cheats_cheat', function(Create $table) {
            $table->addColumn('id', 'int AUTO_INCREMENT');
            $table->addColumn('group_id', 'int');
            $table->addColumn('name', 'varchar', 256);
            $table->addColumn('dll_name', 'varchar', 256);
            $table->addColumn('dll_path', 'varchar', 256);
            $table->addColumn('sys_name', 'varchar', 256);
            $table->addColumn('sys_path', 'varchar', 256);

            $table->addPrimaryKey('id');
            $table->addKey('group_id');
        });

        $this->schemaManager()->createTable('cheats_hwid_reset', function(Create $table) {
            $table->addColumn('id', 'int AUTO_INCREMENT');
            $table->addColumn('user_id', 'int');
            $table->addColumn('date', 'datetime');

            $table->addPrimaryKey('id');
            $table->addKey('user_id');
        });

        $this->schemaManager()->createTable('cheats_user_upgrade_frozen', function(Create $table) {
            $table->addColumn('user_upgrade_record_id', 'int');
            $table->addColumn('user_id', 'int');
            $table->addColumn('purchase_request_key', 'varbinary', 32)->nullable();
            $table->addColumn('user_upgrade_id', 'int');
            $table->addColumn('extra', 'mediumblob');
            $table->addColumn('start_date', 'int');
            $table->addColumn('end_date', 'int');

            $table->addColumn('left_time', 'int');
            $table->addColumn('freeze_date', 'int');

            $table->addUniqueKey(['user_id', 'user_upgrade_id'], 'user_id_upgrade_id');
            $table->addKey('end_date');
            $table->addKey('start_date');
        });

        $this->schemaManager()->createTable('cheats_freeze_history', function(Create $table) {
            $table->addColumn('id', 'int AUTO_INCREMENT');
            $table->addColumn('user_id', 'int');
            $table->addColumn('user_upgrade_record_id', 'int');
            $table->addColumn('date', 'int');

            $table->addPrimaryKey('id');
            $table->addKey('user_id');
        });
	}

	public function upgrade(array $stepParams = [])
	{
		// TODO: Implement upgrade() method.
	}

	public function uninstall(array $stepParams = [])
	{
        $this->schemaManager()->dropTable('cheats_user_hwid');
        $this->schemaManager()->dropTable('cheats_log');
        $this->schemaManager()->dropTable('cheats_cheat');
        $this->schemaManager()->dropTable('cheats_hwid_reset');
        $this->schemaManager()->dropTable('cheats_user_upgrade_frozen');
        $this->schemaManager()->dropTable('cheats_freeze_history');
	}
}