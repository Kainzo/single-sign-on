<?php

/**
 * Model for xensso_master_assoc table
 */
class XenSSO_Master_Model_Association extends XenForo_Model
{

	/**
	 * Get entry by handle
	 * 
	 * @param	string			$handle
	 * 
	 * @return	array|bool						
	 */
	public function getByHandle($handle)
	{
		return $this->_getDb()->fetchRow('
				SELECT *
				FROM xensso_master_assoc
				WHERE handle = ?
		', $handle);
	}

}