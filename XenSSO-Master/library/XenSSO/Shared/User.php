<?php

class XenSSO_Shared_User
{
	
	/**
	 * Create user account
	 * 
	 * @param	array			$properties			
	 * @param	bool			$validateWithMaster
	 * 
	 * @return	bool|array
	 */
	public static function createAccount(array $properties, $validateWithMaster = true)
	{
		
		if ( !isset($properties['email'],$properties['username']))
		{
			throw new XenForo_Exception('Missing email and/or username');
		}
		
		// Check if the user is already registered
		$userModel = new XenForo_Model_User;
		if ($userModel->getUserByEmail($properties['email']))
		{
			return false;
		}
			
		// Set password
		if (isset($properties['password']))
		{
			$password = $properties['password'];
			unset($properties['password']);
		}
		else
		{
			$password = XenSSO_Shared_Secure::getRandomString();
		}
		
		// Write it all to the DB
		$options 	= XenForo_Application::get('options');
		$writer 	= XenForo_DataWriter::create('XenForo_DataWriter_User');
		
		if (class_exists('XenSSO_Slave_DataWriter_Extend_User', false))
		{
			// Whether we want to validate account registration with master SSO server
			XenSSO_Slave_DataWriter_Extend_User::$_validateWithMaster = $validateWithMaster;
		}
		
		// Set registration details
		if ($options->registrationDefaults)
		{
			$writer->bulkSet($options->registrationDefaults, array('ignoreInvalidFields' => true));
		}
		
		// Set registration data
		$data = array(
			'user_group_id'		=> XenForo_Model_User::$defaultRegisteredGroupId,
			'language_id'		=> XenForo_Visitor::getInstance()->get('language_id'),
			'user_state'		=> 'valid'
		);
		$data = array_merge($data, $properties);
		
		// Send registration data to writer
		$writer->bulkSet($data);
		
		if ( ! isset($data['data']))
		{
			$writer->setPassword($password, $password);
		}
		
		// Check for errors
		$writer->preSave();
		if ($writer->getErrors())
		{
			throw new XenForo_Exception('Errors on preSave: ' . XenSSO_Shared_Helpers::parseErrorMessage($writer->getErrors()) );
		}
		
		// Validate birthday
		if ($options->get('registrationSetup', 'requireDob'))
		{
			// dob required
			if (!isset($data['dob_day'], $data['dob_month'], $data['dob_year']))
			{
				throw new XenForo_Exception('Missing DOB input');
			}
			else
			{
				$userProfileModel = new XenForo_Model_UserProfile;
				$userAge = $userProfileModel->getUserAge($writer->getMergedData(), true);
				if ($userAge < 1)
				{
					throw new XenForo_Exception('Invalid DOB');
				}
				else if ($userAge < intval($options->get('registrationSetup', 'minimumAge')))
				{
					throw new XenForo_Exception('DOB Too young');
				}
			}
		}
		
		// Save user to DB
		if ( ! $writer->save())
		{
			XenForo_Error::logException(new Exception( 'Errors on save: ' . XenSSO_Shared_Helpers::parseErrorMessage($writer->getErrors()) ));
			throw new XenForo_Exception('Errors on save: ' . XenSSO_Shared_Helpers::parseErrorMessage($writer->getErrors()) );
		}
		
		// Get resulting user data
		$result = $writer->getMergedData();
		
		if (! empty($result['facebook_auth_id']))
		{
			$externalModel = new XenForo_Model_UserExternal;
			$externalModel->updateExternalAuthAssociation('facebook', $result['facebook_auth_id'], $result['user_id']);
		}
		
		return $result;
	}
	
}