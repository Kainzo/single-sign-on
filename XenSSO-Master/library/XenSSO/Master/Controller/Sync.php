<?php

/**
 * Synchronization controller, handles all sync requests (basically whatever OpenID doesn't support)
 */
class XenSSO_Master_Controller_Sync extends XenForo_ControllerPublic_Abstract
{
	
	/**
	 * Receive the identity for the logged in user
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionMyIdentity()
	{
		$visitor = XenForo_Visitor::getInstance();
		
		// Check if the current visitor is a logged in user
		if ($visitor->user_id > 0)
		{
			
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			
			// Formulate Identity, encode the username to support Unicode characters
			$options  = XenForo_Application::get('options');
			$identity = $options->boardUrl . '/index.php?sso/' . urlencode(urlencode($visitor->username));
			
			// Check if redirect parameter was provided, if so redirect with identity appended, otherwise just echo it
			if ($redirect)
			{
				Zend_OpenId::redirect($redirect . $identity);
			}
			else
			{
				echo $identity;
			}
			
		}
		else
		{
			echo 0;
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Sync registration details, receives user registration from a slave
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionSync()
	{
		
		// Parse authentication data
		$authData = $this->_input->filterSingle('authData', XenForo_Input::STRING);
		$authData = XenSSO_Shared_Secure::decrypt($authData, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
		
		// Prepare DB model
		$userModel = new XenForo_Model_User();
		
		// Check if auth data is valid 
		if (! is_array($authData) OR
			! isset($authData['username'], $authData['email'], $authData['data'], $authData['user_state']))
		{
			return $this->responseErrorRaw('Auth data could not be validated');
		}
		
		// Check if user exists on the DB
		if (
			(! $user = $userModel->getUserByEmail($authData['email'])) AND
			(! $userModel->getUserByName($authData['username']))
			)
		{
			
			// Create the account
			try
			{
				$user = XenSSO_Shared_User::createAccount($authData, false);
			}
			catch (XenForo_Exception $e)
			{
				XenForo_Error::debug('%s',__CLASS__.'::'.__METHOD__.' - createAccount - ' . $e->getMessage());
			}
			
			if ( ! isset($user))
			{
				return $this->responseErrorRaw('User creation failed');
			}
			
		}
		
		// Make sure we have the user details
		if ( ! $user)
		{
			return $this->responseErrorRaw('User retreival failed');
		}
		
		// Get OpenID model and data writer
		$openidModel 	= new XenSSO_Master_Model_User;
		$writer 		= XenForo_DataWriter::create('XenSSO_Master_DataWriter_User');
		
		// Check if user already has an OpenID record
		if ($openidUser = $openidModel->getByUserId($user['user_id']))
		{
			$writer->setExistingData($openidUser);
		}
		else
		{
			// No OpenID record found, so create it
			$writer->set('user_id', $user['user_id']);
			$writer->set('openid_identity', XenForo_Application::get('options')->boardUrl . '/openid/' . $user['username']);
		}
		
		// Set extra data
		$data = $openidUser['extra_data'];
		
		// Ensure data is an array
		if ( !is_array($data))
		{
			$data = array();
		}
		
		// Ensure data has 'sso' entry
		if ( !isset($data['sso']))
		{
			$data['sso'] = array();
		}
		
		// Add authentication key to data
		$data['sso']['key'] 		= sha1(mt_rand(1,9999) . time());
		$data['sso']['key_expires']	= time() + 300;
		$writer->set('extra_data', $data);
		
		// Check for errors
		$writer->preSave();
		if ($writer->getErrors())
		{
			XenForo_Error::logException(new Exception( 'Errors on preSave: ' . XenSSO_Shared_Helpers::parseErrorMessage($writer->getErrors()) ));
			return $this->responseErrorRaw( 'Errors on preSave: ' . XenSSO_Shared_Helpers::parseErrorMessage($writer->getErrors()) );
		}
		
		// Save OpenID record to DB
		$writer->save();
		
		// Print out the authentication key
		echo $data['sso']['key'];
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
		
	}
	
	/**
	 * Sync multiple users at once
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionSyncMultiple()
	{
		
		// Parse input data
		$users = $this->_input->filterSingle('inputData', XenForo_Input::STRING);
		$users = XenSSO_Shared_Secure::decrypt($users, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
		
		// Prep failed var, returned as empty array if all went according to plan
		$failed = array();
		
		// Iterate through users and attempt to create their accounts
		foreach ($users AS $userInput)
		{
			
			// createAccount will throw an exception on errors,
			// apart from the "email already in use" error as it'd be redundant to treat this as an error
			try
			{
				$user = XenSSO_Shared_User::createAccount($userInput, false);
			}
			catch (XenForo_Exception $e)
			{
				// Something went wrong (as in they didn't meet certain criteria's or the user input was invalid)
				$msg = $e->getMessage();
				$md5 = md5($msg);
				
				if ( !isset($failed[$md5]))
				{
					$failed[$md5] = array('error' => $msg, 'usernames' => array());
				}
				
				$failed[$md5]['usernames'][] = $userInput['username'];
			}
			
		}
		
		// output to browser
		echo json_encode($failed);
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
		
	}
	
	/**
	 * Retrieve existing account (if any)
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionRetrieve()
	{
		// Parse input data
		$inputData = $this->_input->filterSingle('inputData', XenForo_Input::STRING);
		$inputData = XenSSO_Shared_Secure::decrypt($inputData, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
		
		// Validate input data
		if ( ! $inputData)
		{
			return $this->responseErrorRaw('Invalid input data');
		}
		
		if (is_array($inputData))
		{
			$inputData = $inputData[0];
		}
		
		// Check if user exists
		$userModel 	= new XenForo_Model_User;
		$user 		= $userModel->getUserByNameOrEmail($inputData);
		
		if ($user)
		{
			$authModel 	= new XenSSO_Shared_Model_Auth;
			$user 		= $authModel->getSyncUserById($user['user_id']);
		}
		
		if ( ! $user)
		{
			echo 0;
		}
		else
		{
			unset($user['user_id']);
			
			$inputData = XenSSO_Shared_Secure::encrypt($user, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
			echo $inputData;
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Validate if user with username exists and if so return their basic data
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionValidateExists()
	{
		
		// Parse input data
		$inputData = $this->_input->filterSingle('inputData', XenForo_Input::STRING);
		$inputData = XenSSO_Shared_Secure::decrypt($inputData, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
		
		list($inputData, $ignoreIf) = $inputData;
		
		// Validate input data
		if ( ! is_array($inputData) OR (! isset($inputData['username']) AND ! isset($inputData['email']) ))
		{
			return $this->responseErrorRaw('Invalid input data');
		}
		
		// Check if user exists
		$userModel = new XenForo_Model_User;
		
		$user = false;
		if (isset($inputData['username']))
		{
			$user = $userModel->getUserByName($inputData['username'], array('join' => XenForo_Model_User::FETCH_USER_PROFILE));
		} else if (isset($inputData['email']))
		{
			$user = $userModel->getUserByEmail($inputData['email'], array('join' => XenForo_Model_User::FETCH_USER_PROFILE));
		}
		
		// The user exists, if we don't have any ignore fields we can already return the result
		if ($user)
		{
			// Validate if ignore fields exists and are valid
			if (empty($ignoreIf) OR ! is_array($ignoreIf))
			{
				echo 1; // Nope, just return the result
			}
			else
			{
				$matched = false;
				foreach ($ignoreIf AS $k => $v)
				{
					if (empty($v) OR ! isset($user[$k]))
					{
						continue;
					}
					
					// If an ignore term matches tell the requestee the entry does not exist
					if (trim(strtolower($user[$k])) == trim(strtolower($v)))
					{
						$matched = true;
						echo 0;
						break;
					}
				}
				
				// Fall back if none if the terms matched
				if ( ! $matched)
				{
					echo 1;
				}
			}
		}
		else
		{
			echo 0;
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
		
	}
	
	/**
	 * Activate given account by email
	 * 
	 * @return	XenForo_ControllerResponse_View							
	 */
	public function actionActivateAccount()
	{
		
		// Parse input data
		$inputData = $this->_input->filterSingle('inputData', XenForo_Input::STRING);
		$inputData = XenSSO_Shared_Secure::decrypt($inputData, XenForo_Application::get('options')->XenSSOMasterSecretPrivate);
		
		// Validate input data
		if ( ! is_array($inputData) OR !isset($inputData['email']))
		{
			return $this->responseErrorRaw('Invalid input data');
		}
		
		// Get user details and activate user
		$userModel = new XenForo_Model_User;
		if ($user = $userModel->getUserByEmail($inputData['email']))
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_User');
			$dw->setExistingData($user['user_id']);
			$dw->advanceRegistrationUserState();
			$dw->save();
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
		
	}
	
	/**
	 * Authenticate by means of a key given out earlier by the actionSync method
	 * 
	 * @return	XenForo_ControllerResponse_View|void
	 */
	public function actionKey()
	{
		
		// Parse authentication data
		$authData = $this->_input->filterSingle('authData', XenForo_Input::STRING);
		$authData = XenSSO_Shared_Secure::decrypt($authData);
		
		// Prepare database models
		$userModel 		= new XenForo_Model_User();
		$openidModel 	= new XenSSO_Master_Model_User();
		
		// Get information about current visitor
		$visitor 		= XenForo_Visitor::getInstance();
		
		// Don't process if the current visitor is already logged in
		if ($visitor->user_id !== 0)
		{
			return $this->responseErrorRaw('Already logged in');
		}
		
		// Validate the authentication data
		if (
			! is_array($authData) OR
			! isset($authData['key'], $authData['email'], $_GET['redirect'])
			)
		{
			return $this->responseErrorRaw('Invalid authentication data');
		}
		
		// Receive user info from database based on email provided in the authentication data
		if ( ! $user = $userModel->getUserByEmail($authData['email']))
		{
			return $this->responseErrorRaw('Could not find user info');
		}
		
		// Receive users' openid info from database based on user id provided in the authentication data
		if ( ! $openidUser = $openidModel->getByUserId($user['user_id']))
		{
			return $this->responseErrorRaw('Could not find OpenID info');
		}
		
		// Validate that authentication key provided matches the one in the database
		if ( ! is_array($openidUser['extra_data']) OR
			 ! isset($openidUser['extra_data']['sso'], $openidUser['extra_data']['sso']['key']) OR
			 $openidUser['extra_data']['sso']['key'] != $authData['key']
			)
		{
			return $this->responseErrorRaw('Auth key miss-match');
		}
		
		// Validate that the authentication key is still valid
		if ($openidUser['extra_data']['sso']['key_expires'] < time())
		{
			return $this->responseErrorRaw('Auth key expired');
		}
		
		// Authenticate the visitor on XF
		$userModel->setUserRememberCookie($user['user_id']);
		XenForo_Model_Ip::log($user['user_id'], 'user', $user['user_id'], 'login');
		$userModel->deleteSessionActivity(0, $this->_request->getClientIp(false));
		$session = XenForo_Application::get('session');
		$session->changeUserId($user['user_id']);
		
		// Redirect using the redirect parameter given in the request
		Zend_OpenId::redirect($_GET['redirect']);
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Output error directly to browser (don't use XF's methods)
	 * 
	 * @param	mixed			$message
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	protected function responseErrorRaw($message)
	{
		echo 'ERROR: ' . var_export($message, true);
		
		XenForo_Error::debug('%s', __CLASS__.' ERROR: ' . $message);
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
}