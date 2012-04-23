<?php

/**
 * Installation actions
 */
class AdvancedUpgrades_Install
{
	
	/**
	 * Install / upgrade actions
	 * 
	 * @param	bool			$existingAddOn
	 * @param	array			$addOnData
	 * 
	 * @return	void
	 */
	public static function install($existingAddOn, $addOnData)
	{
		
		if ( ! $existingAddOn)
		{
			self::createStructure();
		}
		
	}
	
	/**
	 * Create database structure
	 *
	 * Wrapped in try/catch because there is no uninstall method (to prevent data loss)
	 * 
	 * @return	void							
	 */
	protected static function createStructure()
	{
		try
		{
			XenForo_Application::getDb()->query("
				ALTER TABLE `xf_user_upgrade` ADD `purchase_multiple` TINYINT(3)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER `can_purchase`
			");
		} catch (Exception $e) {}
		
		try
		{
			XenForo_Application::getDb()->query("
				ALTER TABLE `xf_user_upgrade_active` ADD `amount` SMALLINT UNSIGNED NOT NULL DEFAULT '1'  AFTER `end_date`
			");
		} catch (Exception $e) {}
		
		try
		{
			XenForo_Application::getDb()->query("
				ALTER TABLE `xf_user_upgrade` ADD `agreement` MEDIUMTEXT  NOT NULL  AFTER `description`
			");
		} catch (Exception $e) {}
		
		try
		{
			XenForo_Application::getDb()->query("
				ALTER TABLE `xf_user_upgrade` ADD `redirect` TEXT  NOT NULL  AFTER `description`;
			");
		} catch (Exception $e) {}
	}
	
}