<?php

/**
 * Validation class, used to validate options
 */
class XenSSO_Slave_Validate
{
	
	/**
	 * Validate the "Master Url" option
	 * 
	 * @param	string			$value
	 * 
	 * @return	bool							
	 */
	public static function optionMasterUrl($value)
	{
		if (defined('XENSSO_SLAVE_INSTALLING'))
		{
			return true;
		}
		
		//return (bool) preg_match('/^(?:https|http)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?\/$/', $value);
		return true;
	}
	
}