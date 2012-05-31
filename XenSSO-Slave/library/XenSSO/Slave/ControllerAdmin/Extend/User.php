<?php

class XenSSO_Slave_ControllerAdmin_Extend_User extends XFCP_XenSSO_Slave_ControllerAdmin_Extend_User
{

	protected function _preDispatch($action)
	{
		XenForo_DataWriter::create('XenForo_DataWriter_User');
		XenSSO_Slave_DataWriter_Extend_User::$_validateWithMaster = false;
		return parent::_preDispatch($action);
	}

}