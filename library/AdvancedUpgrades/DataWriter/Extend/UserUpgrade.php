<?php

class AdvancedUpgrades_DataWriter_Extend_UserUpgrade extends XFCP_AdvancedUpgrades_DataWriter_Extend_UserUpgrade
{
	
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_user_upgrade']['purchase_multiple'] = array('type' => self::TYPE_BOOLEAN, 'default' => 1);
		$fields['xf_user_upgrade']['agreement'] = array('type' => self::TYPE_STRING, 'default' => '');
		$fields['xf_user_upgrade']['redirect'] = array('type' => self::TYPE_STRING, 'default' => '');
		return $fields;
	}
	
}