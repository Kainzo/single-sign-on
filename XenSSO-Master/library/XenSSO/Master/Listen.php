<?php

/**
 * Listener methods for code events
 *
 */
class XenSSO_Master_Listen
{

	/**
	 * Listens to "load_class_datawriter" cod event
	 *
	 * @param	string			$class
	 * @param	array			array
	 *
	 * @return	void							
	 *
	 */
	public static function load_class_datawriter($class, array &$extend)
	{
		// Check if this is the user datawriter and whether we are already extending it
		if ($class == 'XenForo_DataWriter_User' AND ! in_array('XenSSO_Master_DataWriter_Extend_User', $extend))
		{
			$extend[] = 'XenSSO_Master_DataWriter_Extend_User';
		}
		
	}

	public static function controller_pre_dispatch()
	{
		register_shutdown_function(array(__CLASS__, 'pre_shutdown'));
	}
	
	public static function pre_shutdown()
	{
		$session = XenForo_Application::get('session');
		$session->save();
	}


}