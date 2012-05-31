<?php

/**
 * Helper methods that just don't fit anywhere else
 */
class XenSSO_Shared_Helpers
{
	
	/**
	 * Parse dataWriter error messages
	 * 
	 * @param	array			$msg
	 * 
	 * @return	string							
	 */
	public static function parseErrorMessage($msg)
	{
		try
		{
			if (is_array($msg))
			{
				$k = key($msg);
				if ($msg[$k] instanceof XenForo_Phrase)
				{
					$msg = $k . ': ' . $msg[$k]->render();
				}
			}
		}
		catch (Exception $e)
		{}
		
		if ( ! is_string($msg))
		{
			$msg = var_export($msg, true);
		}
		
		return $msg;
	}
	
}