<?php

/**
 * Security class, currently only handles data encryption
 */
class XenSSO_Shared_Secure {

	/**
	 * Decrypt provided data using provided key (if any)
	 * 
	 * @param	mixed				$input
	 * @param	string|null			$key
	 * 
	 * @return	string							
	 */
	public static function encrypt($input, $key = null)
	{
		$input = serialize($input);
		
		// Fall back on using the XenSSO public key if no key is provided
		if ($key === null)
		{
			$options = XenForo_Application::get('options');
			
			if (isset($options->XenSSOSlaveSecretPublic))
			{
				$key = $options->XenSSOSlaveSecretPublic;
			}
			else
			{
				$key = $options->XenSSOMasterSecretPublic;
			}
		}
		
		if (function_exists('mcrypt_encrypt') AND defined('MCRYPT_RIJNDAEL_256') AND 1===0)
		{
			
			// encrypt the data
			return base64_encode(
				mcrypt_encrypt(
					MCRYPT_RIJNDAEL_256, 
					md5($key), 
					$input, 
					MCRYPT_MODE_CBC, 
					md5(md5($key))
				)
			);
			
		}
		else
		{
			$db = XenForo_Application::getDb();
			$encryption = $db->getConnection()->query('
				SELECT aes_encrypt('.$db->quote($input).','.$db->quote(md5($key)).') AS encrypt
			');
			
			if ( ! $encryption)
			{
				XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - Unable to encrypt input'));
				return false;
			}
			
			$encryption = $encryption->fetch_assoc();
			
			return base64_encode($encryption['encrypt']);
		}
	}
	

	/**
	 * Decrypt provided data using provided key (if any)
	 * 
	 * @param	string			$encrypted		
	 * @param	string|null		$key
	 * 
	 * @return	mixed|bool
	 */
	public static function decrypt($encrypted, $key = null)
	{
		
		$encrypted = trim($encrypted);
		
		// if no key is provided use the public key stored in XF options
		if ($key === null)
		{
			$options = XenForo_Application::get('options');
			
			if (isset($options->XenSSOSlaveSecretPublic))
			{
				$key = $options->XenSSOSlaveSecretPublic;
			}
			else
			{
				$key = $options->XenSSOMasterSecretPublic;
			}
		}
		
		if (function_exists('mcrypt_decrypt') AND defined('MCRYPT_RIJNDAEL_256') AND 1===0)
		{
		
			// Decrypt the data
			$result = rtrim(
				mcrypt_decrypt(
					MCRYPT_RIJNDAEL_256, 
					md5($key), 
					base64_decode($encrypted), 
					MCRYPT_MODE_CBC, 
					md5(md5($key))
				), 
				"\0"
			);
			
		}
		else
		{
			$db = XenForo_Application::getDb();
			$encrypted = base64_decode($encrypted);
			$key = md5($key);
			$stmt = $db->getConnection()->prepare('SELECT aes_decrypt(?,?)');
			$stmt->bind_param('ss', $encrypted, $key);
			
			if ( ! $stmt->execute())
			{
				XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - Unable to decrypt input'));
				return false;
			}
			
			$stmt->bind_result($result);
			$stmt->fetch();
			$stmt->close();
		}
		
		// Attempt to unserialize the data
		try
		{
			if ($unserialized = unserialize($result))
			{
				return $unserialized;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	/**
	 * Get a random string
	 * 
	 * @return	string
	 */
	public static function getRandomString() {
		return sha1(mt_rand(0, 10000));
	}

}