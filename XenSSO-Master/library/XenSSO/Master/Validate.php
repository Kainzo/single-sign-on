<?php

/**
 * Validation class, used to validate options
 */
class XenSSO_Master_Validate
{
	
	/**
	 * Validate the "Allowed Domains" option
	 * 
	 * @param	string			$value
	 * 
	 * @return	bool							
	 */
	public static function optionAllowedDomains($value)
	{
		
		// don't run if the addon is being installed as XF seems to call the validation BEFORE setting the default data
		if (defined('XENSSO_MASTER_INSTALLING'))
		{
			return true;
		}
		
		// Parse domains
		$domains = explode("\n", $value);
		foreach ($domains AS $domain)
		{
			$domain = trim($domain);
			
			// Validate format of domain
			if ( ! preg_match('/^[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}$/', $domain))
			{
				return false;
			}
		}
		
		return true;
	}
	
}