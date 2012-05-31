<?php

/**
 * Install class, used for installs, upgrades and uninstalls
 */
class XenSSO_Master_Install
{
	
	/**
	 * Perform install or update
	 * 
	 * @param	bool|array			$existingAddOn	
	 * @param	array				$addOnData
	 * 
	 * @return	void
	 */
	public static function install($existingAddOn, $addOnData)
	{
		
		define('XENSSO_MASTER_INSTALLING', true);
		
		if ( ! $existingAddOn)
		{
			self::createStructure();
		}
		
		if ($existingAddOn['version_id'] <= 6)
		{
			self::update6();
		}
		
	}
	
	/**
	 * Perform uninstall (wipe stored data)
	 * 
	 * @return	void							
	 */
	public static function uninstall()
	{
		self::dropStructure();
	}
	
	/**
	 * 1.0.4 Update
	 * 
	 * @return	void							
	 */
	protected static function update19()
	{
		XenForo_Application::getDb()->query("
			ALTER TABLE `xensso_master_user` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xensso_master_assoc` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci
		");
	}

	/**
	 * Beta 6 update
	 * 
	 * @return	void							
	 */
	protected static function update6()
	{
		self::dropStructure();
		self::createStructure();
	}
	
	/**
	 * Create database tables
	 * 
	 * @return	void							
	 */
	protected static function createStructure()
	{
		XenForo_Application::getDb()->query("
			CREATE TABLE `xensso_master_user` (
			  `user_id` int(10) unsigned NOT NULL,
			  `openid_identity` varchar(255) NOT NULL DEFAULT '',
			  `openid_sites` mediumtext NOT NULL,
			  `extra_data` mediumtext NOT NULL,
			  UNIQUE KEY `openid_identity` (`openid_identity`),
			  KEY `user_id` (`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
		
		XenForo_Application::getDb()->query("
			CREATE TABLE IF NOT EXISTS `xensso_master_assoc` (
			  `handle` varchar(100) NOT NULL DEFAULT '',
			  `macfunc` enum('sha1','sha256') NOT NULL DEFAULT 'sha1',
			  `secret` varchar(100) NOT NULL DEFAULT '',
			  `expires` int(11) NOT NULL,
			  PRIMARY KEY (`handle`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
	}
	
	/**
	 * Drop database tables
	 * 
	 * @return	void							
	 */
	protected static function dropStructure()
	{
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xensso_master_user`
		");
		
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xensso_master_assoc`
		");
	}
	
}