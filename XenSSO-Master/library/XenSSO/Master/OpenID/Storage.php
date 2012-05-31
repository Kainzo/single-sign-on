<?php

/**
 * OpenID storage
 * Maps to database tables
 */
class XenSSO_Master_OpenID_Storage extends Zend_OpenId_Provider_Storage
{

	/**
	 * Stores information about session identified by $handle
	 *
	 * @param string $handle assiciation handle
	 * @param string $macFunc HMAC function (sha1 or sha256)
	 * @param string $secret shared secret
	 * @param string $expires expiration UNIX time
	 * @return void
	 */
	public function addAssociation($handle, $macFunc, $secret, $expires)
	{
		$writer = XenForo_DataWriter::create('XenSSO_Master_DataWriter_Association');
		$writer->bulkSet(array(
			'handle'    => $handle,
			'macfunc'   => trim($macFunc),
			'secret'    => base64_encode($secret),
			'expires'   => $expires
		));

		$writer->save();
	}

	/**
	 * Gets information about association identified by $handle
	 * Returns true if given association found and not expired and false
	 * otherwise
	 *
	 * @param string $handle assiciation handle
	 * @param string &$macFunc HMAC function (sha1 or sha256)
	 * @param string &$secret shared secret
	 * @param string &$expires expiration UNIX time
	 * @return bool
	 */
	public function getAssociation($handle, &$macFunc, &$secret, &$expires)
	{
		$assocModel = new XenSSO_Master_Model_Association;
		$assoc      = $assocModel->getByHandle($handle);

		if ( ! $assoc OR $assoc['handle'] != $handle)
		{
			return false;
		}

		$macFunc 	= $assoc['macfunc'];
		$secret 	= base64_decode($assoc['secret']);
		$expires 	= $assoc['expires'];

		return true;
	}

	/**
	 * Creates new storage for user
	 * exists
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function addUser($id, $password)
	{
		return $this->checkUser($id, '');
	}

	/**
	 * Returns true if user with given $id exists and false otherwise
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function hasUser($id)
	{
		return $this->checkUser($id, '');
	}

	/**
	 * Verify if user exists, and if not creates it (if it exists in the XF DB)
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function checkUser($id, $password)
	{
		$userModel = new XenForo_Model_User;
		$user = $userModel->getUserByNameOrEmail(urldecode(basename($id)));

		if ( ! $user)
		{
			return false;
		}
		
		$ssoModel 	= new XenSSO_Master_Model_User;
		$ssoUser 	= $ssoModel->getByIdentity($id);

		if ($ssoUser)
		{
			if ($ssoUser['user_id'] != $user['user_id'])
			{
				$writer = XenForo_DataWriter::create('XenSSO_Master_DataWriter_User');
				$writer->setExistingData($ssoUser);
				$writer->delete();
			}
			else
			{
				return true;
			}
		}

		$writer = XenForo_DataWriter::create('XenSSO_Master_DataWriter_User');
		$writer->set('openid_identity', $id);
		$writer->set('user_id', $user['user_id']);

		$writer->save();

		return true;
	}

	/**
	 * Returns array of all trusted/untrusted sites for given user identified
	 * by $id
	 *
	 * @param string $id user identity URL
	 * @return array
	 */
	public function getTrustedSites($id)
	{
		$userModel 	= new XenSSO_Master_Model_User;
		$user 		= $userModel->getByIdentity($id, false);

		if ( ! $user)
		{
			return array();
		}

		$sites = unserialize($user['openid_sites']);

		return $sites ? $sites : array();
	}

	/**
	 * Stores information about trusted/untrusted site for given user
	 *
	 * @param string $id user identity URL
	 * @param string $site site URL
	 * @param mixed $trusted trust data from extensions or just a boolean value
	 * @return bool
	 */
	public function addSite($id, $site, $trusted)
	{
		if ( ! $this->hasUser($id))
		{
			return false;
		}

		$userModel 	= new XenSSO_Master_Model_User;
		$user 		= $userModel->getByIdentity($id);

		$writer = XenForo_DataWriter::create('XenSSO_Master_DataWriter_User');

		$sites = array();
		$writer->setExistingData($user);

		$sites = $user['openid_sites'];
		$sites[$site] = $trusted;

		$writer->set('openid_sites', $sites);

		$writer->save();
	}
}
