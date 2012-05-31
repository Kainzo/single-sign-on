<?php

/**
 * Model for xensso_master_assoc table
 */
class XenSSO_Master_Model_User extends XenForo_Model
{

	/**
	 * Get by User ID
	 * 
	 * @param	int			$iduser			
	 * @param	bool		$unserialize
	 * 
	 * @return	array|bool|$this->_unserialize()							
	 */
	public function getByUserId($iduser, $unserialize = true)
	{
		$result = $this->_getDb()->fetchRow('
				SELECT *
				FROM xensso_master_user
				WHERE user_id = ?
		', $iduser);

		return $unserialize ? $this->_unserialize($result) : $result;
	}

	/**
	 * Get by identity
	 * 
	 * @param	string			$identity		
	 * @param	bool			$unserialize
	 * 
	 * @return	array|bool|$this->_unserialize()
	 */
	public function getByIdentity($identity, $unserialize = true)
	{
		$result = $this->_getDb()->fetchRow('
				SELECT *
				FROM xensso_master_user
				WHERE openid_identity = ?
		', $identity);

		return $unserialize ? $this->_unserialize($result) : $result;
	}

	/**
	 * Unserialize serializable data
	 * 
	 * @param	array|bool			$result
	 * 
	 * @return	array|bool							
	 */
	private function _unserialize($result)
	{
		if ( ! $result)
		{
			return $result;
		}

		$result['openid_sites'] = unserialize($result['openid_sites']);
		$result['extra_data'] = unserialize($result['extra_data']);

		return $result;
	}

}