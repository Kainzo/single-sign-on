<?php

class AdvancedUpgrades_Listen
{
	
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
	
	public static function load_class_model($class, array &$extend)
	{
		if ($class == 'XenForo_Model_UserUpgrade' AND ! in_array('AdvancedUpgrades_Model_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_Model_Extend_UserUpgrade';
		}
	}
	
	public static function load_class_datawriter($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_UserUpgrade' AND ! in_array('AdvancedUpgrades_DataWriter_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_DataWriter_Extend_UserUpgrade';
		}
	}
	
	public static function load_class_controller($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerAdmin_UserUpgrade' AND ! in_array('AdvancedUpgrades_ControllerPublic_Extend_UserUpgrade', $extend))
		{
			$extend[] = 'AdvancedUpgrades_ControllerPublic_Extend_UserUpgrade';
		}
	}
	
}