<?php

/**
 * Listener methods for code events
 *
 */
class XenSSO_Slave_Listen
{
	
	public static $_authKey = false;
	
	public static function controller_pre_dispatch(XenForo_Controller $controller, $action)
	{
		$session = XenForo_Application::get('session');
		
		if ($session->get('xensso_auth_key'))
		{
			self::$_authKey = $session->get('xensso_auth_key');
			$session->remove('xensso_auth_key');
		}
	}

	public static function load_class_controller($class, array &$extend)
	{
		// Check if this is the login controller and whether we are already extending it
		if ($class == 'XenForo_ControllerPublic_Login' AND ! in_array('XenSSO_Slave_Controller_Extend_Login', $extend))
		{
			$extend[] = 'XenSSO_Slave_Controller_Extend_Login';
		}
		
		// Check if this is the register controller and whether we are already extending it
		if ($class == 'XenForo_ControllerPublic_Register' AND ! in_array('XenSSO_Slave_Controller_Extend_Register', $extend))
		{
			$extend[] = 'XenSSO_Slave_Controller_Extend_Register';
		}
		
		// Check if this is the AccountConfirmation controller and whether we are already extending it
		if ($class == 'XenForo_ControllerPublic_AccountConfirmation' AND ! in_array('XenSSO_Slave_Controller_Extend_AccountConfirmation', $extend))
		{
			$extend[] = 'XenSSO_Slave_Controller_Extend_AccountConfirmation';
		}
		
		/* Extend XenForo_ControllerAdmin_User */
		if ($class == 'XenForo_ControllerAdmin_User' AND ! in_array('XenSSO_Slave_ControllerAdmin_Extend_User', $extend))
		{
			$extend[] = 'XenSSO_Slave_ControllerAdmin_Extend_User';
		}
		/* Extend End */
	}

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
		if ($class == 'XenForo_DataWriter_User' AND ! in_array('XenSSO_Slave_DataWriter_Extend_User', $extend))
		{
			$extend[] = 'XenSSO_Slave_DataWriter_Extend_User';
		}
	}

	/**
	 * Listens to "load_class_controller" code event
	 *
	 * Used to embed javascript, either to check for authentication
	 * data on master or login to the master transparently
	 *
	 * @param	string						$hookName		
	 * @param	string						$contents		
	 * @param	array						$hookParams		
	 * @param	XenForo_Template_Abstract	$template
	 *
	 * @return	void
	 *
	 */
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		
		// We only want to add data to the page header
		if ($hookName != 'page_container_head')
		{
			return;
		}
		
		// Get info about visitor and session, and get XF options
		$visitor 	= XenForo_Visitor::getInstance();
		$options 	= XenForo_Application::get('options');
		
		// Set default action, in case neither of the following statements trigger
		$include = false;
		
		// Check if we have an auth key
		// in case we do we need to try to transparently login on the master
		if (self::$_authKey AND $visitor->user_id > 0)
		{
			// Set and encrypt auth data
			$authData	= XenSSO_Shared_Secure::encrypt(array(
				'email'	=> $visitor->email,
				'key'	=> self::$_authKey
			));
			
			// Append javascript to contents
			$contents 	.= '<script>
				var xensso_auth_data = "'.$authData.'";
				var xensso_master_url = "'.$options->XenSSOMasterUrl.'";
			</script>';
			
			// Update include variable so it knows we added content
			$include = true;
		}
		
		// Check if the user is not logged in, and if so, include javascript to login transparently
		if ($visitor->user_id == 0)
		{
			
			// Get visitor session
			$session = XenForo_Application::get('session');
			$attempt = isset($_COOKIE['attemptLogin']) ? $_COOKIE['attemptLogin'] : false;
			
			// Check if this is a fresh session, we don't want to do this every time someone switches to another page
			if ( ! $session->get('previousActivity') AND ! $attempt)
			{
				// Append javascript to contents
				$contents 	.= '
					<script>
						var xensso_attempt_login = true;
						var xensso_master_url = "'.$options->XenSSOMasterUrl.'";
					</script>
				';
			
				// Update include variable so it knows we added content
				$include = true;
				
				// Update session so it doesn't try this again
				setcookie('attemptLogin', true);
			}
		}
		
		// Check if javascript was added, if so we'll need to include the js library that actually uses it
		if ($include)
		{
			$contents .= '<script src="'.$options->boardUrl.'/js/xensso/slave.js"></script>';
		}
	}


}