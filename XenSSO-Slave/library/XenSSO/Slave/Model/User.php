<?php

/**
 * Model for xensso_slave_user table
 */
class XenSSO_Slave_Model_User extends XenForo_Model
{

	/**
	 * Get user ID by OpenID
	 * 
	 * @param	string			$identity
	 * 
	 * @return	array|bool						
	 */
	public function getUserIdByOpenId($identity)
	{
		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xensso_slave_user
				WHERE openid_identity = ?
		', $identity);
	}

	/**
	 * Get user by User ID
	 * 
	 * @param	int			$userId
	 * 
	 * @return	array|bool				
	 */
	public function getUserByUserId($userId)
	{
		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xensso_slave_user
				WHERE user_id = ?
		', $userId);
	}

}