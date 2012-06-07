<?php

/**
 * Listen for code events
 */
class AdvancedUpgrades_Listen
{
	
	/**
	 * Template Create
	 * 
	 * @param	string						$templateName	
	 * @param	array						array			
	 * @param	XenForo_Template_Abstract	$template
	 * 
	 * @return	void										
	 */
	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'account_upgrades')
		{
			$templateName = 'account_upgrades_advanced';
		}
		
		if ($templateName == 'user_upgrade_edit')
		{
			$templateName = 'user_upgrade_edit_advanced';
		}
		
		if ($templateName == 'user_upgrade_active')
		{
			$templateName = 'user_upgrade_active_advanced';
		}
	}
	
	/**
	 * Load model
	 * 
	 * @param	string			$class			
	 * @param	array			array
	 * 
	 * @return	void							
	 */
	public static function load_class_model($class, array &$extend)
	{
		if ($class == 'XenForo_Model_UserUpgrade' AND ! in_array('AdvancedUpgrades_Model_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_Model_Extend_UserUpgrade';
		}
	}
	
	/**
	 * Load datawriter
	 * 
	 * @param	string			$class			
	 * @param	array			array
	 * 
	 * @return	void							
	 */
	public static function load_class_datawriter($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_UserUpgrade' AND ! in_array('AdvancedUpgrades_DataWriter_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_DataWriter_Extend_UserUpgrade';
		}
	}
	
	/**
	 * Load controller
	 * 
	 * @param	string			$class
	 * @param	array			array
	 * 
	 * @return	void							
	 */
	public static function load_class_controller($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerAdmin_UserUpgrade' AND ! in_array('AdvancedUpgrades_ControllerAdmin_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_ControllerAdmin_Extend_UserUpgrade';
		}
		
		if ($class == 'XenForo_ControllerAdmin_Option' AND ! in_array('AdvancedUpgrades_ControllerAdmin_Extend_Option', $extend))
		{
			$extend[] = 'AdvancedUpgrades_ControllerAdmin_Extend_Option';
		}
	}
	
}