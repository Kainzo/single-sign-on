<?php

/**
 * Datawriter for xensso_master_assoc table
 */
class XenSSO_Master_DataWriter_Association extends XenForo_DataWriter
{
	
	/**
	 * Get fields managed by this datawriter
	 * 
	 * @return	array							
	 */
	protected function _getFields()
	{
		return array(
			'xensso_master_assoc' => array(
				'handle' 	=> array('type' => self::TYPE_STRING,	'maxLength' => 100, 'required' => true),
				'macfunc'	=> array('type' => self::TYPE_STRING,	'maxLength' => 100, 'required' => true, 'default' => 'sha1'),
				'secret' 	=> array('type' => self::TYPE_STRING,	'maxLength' => 100, 'required' => true),
				'expires'	=> array('type' => self::TYPE_INT,   	'required'	=> true)
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
		if (!$handle = $this->_getExistingPrimaryKey($data, 'handle'))
		{
			return false;
		}

		return array('xensso_master_assoc' => $this->getModelFromCache('XenSSO_Master_Model_Association')->getByHandle($handle));
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
		return 'handle = ' . $this->_db->quote($this->getExisting('handle'));
	}

}