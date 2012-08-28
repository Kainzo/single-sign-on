<?php

class XenSSO_Slave_Controller_Consumer extends XenForo_ControllerPublic_Abstract
{
	
	/**
	 * Perform OpenID Login
	 * 
	 * @return	void|$this->responseLoginError
	 */
	public function actionLogin()
	{
		// Get OpenID identity and validate it
		$identity = $this->getIdentity();
		if ( $identity == null)
		{
			return $this->responseLoginError(new XenForo_Phrase('xensso_slave_missing_identity'));
		}
		
		// Gather OpenID request data
		$dir 		= XenForo_Application::getInstance()->getRootDir() . '/internal_data/xensso/';
		$storage 	= new Zend_OpenId_Consumer_Storage_File($dir);
		$consumer 	= new XenSSO_Slave_OpenID_Consumer($storage);
		$sreg 		= $this->getSreg();
		$returnTo 	= $this->getReturnTo();
		
		// set additional parameters (eg. authData)
		$consumer->setParams($this->getRequestParams());
		
		// Attempt login
		if ( ! $consumer->login($identity , $returnTo, null, $sreg)) 
		{
			// Something was wrong with the input, this only gets triggered if the request to the provider didn't even happen
			XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - Error: '.$consumer->getError() ));
			return $this->responseLoginError($consumer->getError());
		}
	}
	
	/**
	 * Receive callback from provider post-login attempt
	 * 
	 * @return	$this->responseLoginError|$this->userLogin
	 */
	public function actionCallback()
	{
		// Get user input
		$openidMode = $this->_input->filterSingle('openid_mode', XenForo_Input::STRING);
		$username 	= $this->_input->filterSingle('username', XenForo_Input::STRING);
		$errorType 	= $this->_input->filterSingle('errorType', XenForo_Input::STRING);
		
		// Is this callback for a failed login
		if ($openidMode == 'cancel')
		{
			// Check if we have a username to include with the error
			if ( ! empty($errorType))
			{
				$text = new XenForo_Phrase($errorType, array('name' => $username));
			}
			else if ( ! empty($username))
			{
				$text = new XenForo_Phrase('requested_user_x_not_found', array('name' => $username));
			}
			else
			{
				$text = new XenForo_Phrase('requested_user_not_found');
			}
			
			// Show login page with error
			return $this->responseLoginError($text, $username);
		}
		
		// Gather OpenID request data
		$dir 		= XenForo_Application::getInstance()->getRootDir() . '/internal_data/xensso/';
		$storage 	= new Zend_OpenId_Consumer_Storage_File($dir);
		$consumer 	= new XenSSO_Slave_OpenID_Consumer($storage);
		$sreg 		= $this->getSreg();
		
		// Validate if returned data matches up with the request
		if ($openidMode != "id_res" OR ! $verify = $consumer->verify($_GET, $id, $sreg))
		{
			if (isset($verify))
			{
				// Something didn't match, we can't trust this data
				XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - Error: '.$consumer->getError() ));
			}
			
			return $this->responseLoginError($consumer->getError());
		}
		
		// Returned data is valid, perform login
		return $this->userLogin($consumer, $sreg);
	}
	
	/**
	 * Perform login on XF
	 * 
	 * @param	XenSSO_Slave_OpenID_Consumer		$consumer			
	 * @param	Zend_OpenId_Extension_Sreg			$sreg				
	 * @param	Boolean								$allowRegistration
	 * 
	 * @return	mixed
	 */
	protected function userLogin(XenSSO_Slave_OpenID_Consumer $consumer, Zend_OpenId_Extension_Sreg $sreg, $allowRegistration = true)
	{
		
		// Get OpenID identity
		$identity = $this->getIdentity();
		
		// Get OpenID record for identity (if any)
		$openIdModel 	= new XenSSO_Slave_Model_User;
		$userOpenId 	= $openIdModel->getUserIdByOpenId($identity);
		
		// Check if we have a record
		if ( ! $userOpenId)
		{
			// Nope, can we make one?
			if ($allowRegistration)
			{
				// Register an OpenID record and possibly a new user
				return $this->userRegister($consumer, $sreg);
			}
			else
			{
				// Nope, output error
				return $this->responseLoginError(new XenForo_Phrase('xensso_slave_login_failed_noreg')); 
			}
		}
		
		// Get XF user model
		$userModel 	= new XenForo_Model_User;
		
		// Validate user exists
		if ( ! $user = $userModel->getUserById($userOpenId['user_id']))
		{
			$writer = XenForo_DataWriter::create('XenSSO_Slave_DataWriter_User');
			$writer->setExistingData($userOpenId);
			$writer->delete();
			
			return $this->userLogin($consumer, $sreg, $allowRegistration);
		}
		
		// Log login
		XenForo_Model_Ip::log($userOpenId['user_id'], 'user', $userOpenId['user_id'], 'login');
		
		// Delete session activity to this point (we're starting a new one)
		$userModel->deleteSessionActivity(0, $this->_request->getClientIp(false));
		
		// Get active session and update it with the newly logged in user id
		$session = XenForo_Application::get('session');
		$session->changeUserId($userOpenId['user_id']);
		
		// Set up visitor instance
		XenForo_Visitor::setup($userOpenId['user_id']);
		
		// Succesful login, redirect
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->getDynamicRedirect()
		);
	}
	
	/**
	 * Perform XF registration if the user does not exist yet
	 * 
	 * @param	XenSSO_Slave_OpenID_Consumer	$consumer		
	 * @param	Zend_OpenId_Extension_Sreg			$sreg
	 * 
	 * @return	$this->responseLoginError|$this->userLogin
	 */
	protected function userRegister(XenSSO_Slave_OpenID_Consumer $consumer, Zend_OpenId_Extension_Sreg $sreg)
	{
		
		// get sreg properties (profile info)
		$properties = $sreg->getProperties();
		
		// Validate if we have all required properties
		if ( ! $this->validateProperties($properties))
		{
			return $this->responseLoginError(new XenForo_Phrase('xensso_slave_registration_missing_or_duplicate'));
		}
		
		// Check if the user is already registered
		$userModel = new XenForo_Model_User;
		if ( ! $user = $userModel->getUserByEmail($properties['email']))
		{
			$data = array(
				'username'   		=> $properties['nickname'],
				'email'				=> $properties['email']
			);
			
			// Parse authentication data
			$authData = $this->_input->filterSingle('authData', XenForo_Input::STRING);
			$authData = XenSSO_Shared_Secure::decrypt($authData);
			
			if ($properties['dob'] != 'false')
			{
				list($data['dob_day'], $data['dob_month'], $data['dob_year']) = explode('/', $properties['dob']);
			}
			
			if ($authData)
			{
				$data = array_merge($data, $authData);
			}
			
			try
			{
				$user = XenSSO_Shared_User::createAccount($data, false);
			}
			catch (XenForo_Exception $e)
			{
				XenForo_Error::debug('%s',__CLASS__.'::'.__METHOD__.' - createAccount - ' . $e->getMessage());
			}
			
			if ( ! $user)
			{
				return $this->responseLoginError(new XenForo_Phrase('xensso_slave_registration_missing_or_duplicate'));
			}
		}
		
		// Save OpenID data
		$identity = $this->getIdentity();
		
		$writer = XenForo_DataWriter::create('XenSSO_Slave_DataWriter_User');
		$writer->bulkSet(array(
			'user_id'			=> $user['user_id'],
			'openid_identity'	=> $identity,
			'openid_sreg'		=> '{}'
		));
		
		$writer->save();
		
		// All done, now login
		return $this->userLogin($consumer, $sreg, false);
		
	}
	
	/**
	 * Return login page with error
	 * 
	 * @param	string|XenForo_Phrase		$text			
	 * @param	string						$username
	 * 
	 * @return	$this->responseView
	 */
	protected function responseLoginError($text, $username = '')
	{
		return $this->responseView('XenForo_ViewPublic_Login', 'error_with_login', array(
			'text' 			=> $text,
			'defaultLogin' 	=> $username,
			'captcha' 		=> XenForo_Captcha_Abstract::createDefault(true),
			'redirect' 		=> $this->getDynamicRedirect()
		));
	}

	/**
	 * Get OpenID Identity
	 * 
	 * @return	null|string
	 */
	protected function getIdentity()
	{
		
		// Get identity from user input
		$identity = $this->_input->filterSingle('openid_identity', XenForo_Input::STRING);
		
		// Check if identity is empty and if so, try to formulate it from the XenSSO session
		if (empty($identity))
		{
			// Get XenSSO session
			$session 	= XenForo_Application::get('session');
			
			// Check if username is set in session
			if ( ! $session->get('userName'))
			{
				return null;
			}
			
			// Formulate identity
			$options 	= XenForo_Application::get('options');
			$masterUrl 	= $options->XenSSOMasterUrl;
			$identity 	= $masterUrl . 'index.php?sso/' . urlencode($session->get('userName'));
		}
		
		return $identity;
	}

	/**
	 * Get return address
	 * 
	 * @return	string
	 */
	protected function getReturnTo()
	{
		// Build link and return it
		return XenForo_Link::buildPublicLink('full:sso-slave/callback', null, $this->getCallbackParams());
	}
	
	/**
	 * Get callback params, will be used when the provider sends back the results
	 * 
	 * @return	array
	 */
	protected function getCallbackParams()
	{
		// Try to get redirect address from XenSSO session, otherwise use $this->getDynamicRedirect()
		$session 	= XenForo_Application::get('session');
		$redirect 	= $session->get('redirect') ? $session->get('redirect') : $this->getDynamicRedirect();
		
		// Set default required params
		$params 	= array( 'redirect' => $redirect, 'username' => basename($this->getIdentity()) );
		
		// Append additional params, if any
		if ($session->get('callbackParams'))
		{
			$params = array_merge($params,$session->get('callbackParams'));
		}
		
		return $params;
	}

	/**
	 * Get request params, will be used in the initial openid request
	 * 
	 * @return	array
	 */
	protected function getRequestParams()
	{
		$session = XenForo_Application::get('session');
		return $session->get('requestParams') ? $session->get('requestParams') : array();
	}
	
	/**
	 * Get instantiated sreg extension
	 * 
	 * @return	Zend_OpenId_Extension_Sreg							
	 */
	protected function getSreg()
	{
		return new Zend_OpenId_Extension_Sreg(array(
			'nickname'=>true,
			'email'=>true,
			'dob'=>true
		), null, 1.1);
	}
	
	/**
	 * Validate the user properties prior to account creation
	 * 
	 * @param	array			$properties
	 * 
	 * @return	bool							
	 */
	protected function validateProperties(array $properties)
	{
		$userModel 	= new XenForo_Model_User;

		if (
			! isset($properties['nickname']) OR
			! isset($properties['email']) OR
			($user = $userModel->getUserByName($properties['nickname']) AND
			 $user['email'] != $properties['email'])
		)
		{
			return false;
		}

		return true;
	}

}