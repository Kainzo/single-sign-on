<?php

/**
 * Install class, used for installs, upgrades and uninstalls
 */
class XenSSO_Slave_Install
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
		
		define('XENSSO_SLAVE_INSTALLING', true);
		
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
		self::purgeStorage();
		self::dropStructure();
	}

	/**
	 * Beta 6 update
	 * 
	 * @return	void							
	 */
	protected static function update6()
	{
		self::purgeStorage();
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
			CREATE TABLE IF NOT EXISTS `xensso_slave_user` (
			  `openid_identity` varchar(255) NOT NULL DEFAULT '',
			  `openid_sreg` mediumtext NOT NULL,
			  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`openid_identity`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
			DROP TABLE IF EXISTS `xensso_slave_user`
		");
	}
	
	/**
	 * Purge OpenID storage
	 * 
	 * @return	void
	 */
	protected static function purgeStorage()
	{
		$tmp = getenv('TMP');
		if (empty($tmp))
		{
			$tmp = getenv('TEMP');
			if (empty($tmp))
			{
				$tmp = "/tmp";
			}
		}
		
		$user = get_current_user();
		if (is_string($user) && !empty($user))
		{
			$tmp .= '/' . $user;
		}
		
		$dir = $tmp . '/openid/consumer/';
		
		if ( ! is_dir($dir))
		{
			return;
		}
		
		$files = scandir($dir);
		
		foreach ($files AS $file)
		{
			if (is_dir($file))
			{
				continue;
			}
			
			@unlink($dir . $file);
		}
	}
	
}