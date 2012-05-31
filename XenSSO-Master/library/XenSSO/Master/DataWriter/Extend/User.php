<?php

class XenSSO_Master_DataWriter_Extend_User extends XFCP_XenSSO_Master_DataWriter_Extend_User
{
	
	/**
	 * Delete related OpenID entry when username or email changes
	 * 
	 * @return	void
	 */	
	protected function _postSave()
	{
		if ($this->isChanged('username') OR $this->isChanged('email'))
		{
			$db = $this->_db;
			$userId = $this->get('user_id');
			$userIdQuoted = $db->quote($userId);
			
			$db->delete('xensso_master_user', "user_id = $userIdQuoted");
		}
		
		parent::_postSave();
	}
	
	/**
	 * Delete related OpenID entry
	 * 
	 * @return	void
	 */
	protected function _postDelete()
	{
		$db = $this->_db;
		$userId = $this->get('user_id');
		$userIdQuoted = $db->quote($userId);
		
		$db->delete('xensso_master_user', "user_id = $userIdQuoted");
		
		parent::_postDelete();
	}
	
}