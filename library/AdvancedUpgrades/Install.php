<?php

class AdvancedUpgrades_Install
{
	
	public static function install()
	{
		
		if ( ! $existingAddOn)
		{
			self::createStructure();
		}
		
	}
	
	protected static function createStructure()
	{
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_user_upgrade` ADD `purchase_multiple` TINYINT(3)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER `can_purchase`
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_user_upgrade_active` ADD `amount` SMALLINT UNSIGNED NOT NULL DEFAULT '1'  AFTER `end_date`
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_user_upgrade` ADD `agreement` MEDIUMTEXT  NOT NULL  AFTER `description`
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_user_upgrade` ADD `redirect` TEXT  NOT NULL  AFTER `description`;
		");

		
	}
	
}