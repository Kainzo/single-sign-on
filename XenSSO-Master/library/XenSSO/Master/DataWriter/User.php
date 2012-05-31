<?php

/**
 * Datawriter for xensso_master_user table
 */
class XenSSO_Master_DataWriter_User extends XenForo_DataWriter
{
	
	/**
	 * Get fields managed by this datawriter
	 * 
	 * @return	array							
	 */
	protected function _getFields()
	{
		return array(
			'xensso_master_user' => array(
				'user_id'					=> array('type' => self::TYPE_INT, 'required' => true),
				'openid_identity'			=> array('type' => self::TYPE_STRING, 'maxLength' => 255),
				'openid_sites'				=> array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'extra_data'				=> array('type' => self::TYPE_SERIALIZED, 'default' => '')
			)
		);
	}

	/**
	 * Get existing data
	 * 
	 * @param	int			$data
	 * 
	 * @return	array							
	 */
	protected function _getExistingData($data)
	{
		if ( ! $identity = $this->_getExistingPrimaryKey($data, 'openid_identity'))
		{
			return false;
		}

		return array('xensso_master_user' => $this->getModelFromCache('XenSSO_Master_Model_User')->getByIdentity($identity));
	}

	/**
	 * Get update condition
	 * 
	 * @param	string			$tableName
	 * 
	 * @return	string							
	 */
	protected function _getUpdateCondition($tableName)
	{
		return 'openid_identity = ' . $this->_db->quote($this->getExisting('openid_identity'));
	}

}