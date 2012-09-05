<?php

class XenSSO_Master_OpenID_Provider_XenForo extends XenSSO_Master_OpenID_Provider
{
	
    /**
     * Handles HTTP request from consumer
     *
     * @param array $params GET or POST variables. If this parameter is omited
     *  or set to null, then $_GET or $_POST superglobal variable is used
     *  according to REQUEST_METHOD.
     * @param mixed $extensions extension object or array of extensions objects
     * @param Zend_Controller_Response_Abstract $response an optional response
     *  object to perform HTTP or HTML form redirection
     * @return mixed
     */
    public function handle($params=null, $extensions=null,
                           Zend_Controller_Response_Abstract $response = null)
    {
        if ($params === null) {
            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $params = $_GET;
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $params = $_POST;
            } else {
                return false;
            }
        }
        $version = 1.1;
        if (isset($params['openid_ns']) &&
            $params['openid_ns'] == Zend_OpenId::NS_2_0) {
            $version = 2.0;
        }
		
        if (isset($params['openid_mode'])) {
            if ($params['openid_mode'] == 'associate') {
                $response = $this->_associate($version, $params);
                $ret = '';
                foreach ($response as $key => $val) {
                    $ret .= $key . ':' . $val . "\n";
                }
                return $ret;
            } else if ($params['openid_mode'] == 'checkid_immediate') {
                $ret = $this->_checkId($version, $params, 1, $extensions, $response);
                if (is_bool($ret)) return $ret;
                if (!empty($params['openid_return_to'])) {
					Zend_OpenId::redirect($params['openid_return_to'], $ret, $response);
                }
                return true;
            } else if ($params['openid_mode'] == 'checkid_setup') {
                $ret = $this->_checkId($version, $params, 0, $extensions, $response);
                if (is_bool($ret)) return $ret;
                if (!empty($params['openid_return_to'])) {
					Zend_OpenId::redirect($params['openid_return_to'], $ret, $response);
                }
                return true;
            } else if ($params['openid_mode'] == 'check_authentication') {
                $response = $this->_checkAuthentication($version, $params);
                $ret = '';
                foreach ($response as $key => $val) {
                    $ret .= $key . ':' . $val . "\n";
                }
                return $ret;
            }
        }
        return false;
    }
	
	/**
	 * Performs login of user with given $id and $password
	 * Returns true in case of success and false otherwise
	 *
	 * @param string $id user identity URL
	 * @param array $params request params
	 * @return bool
	 */
	public function login($id, $params)
	{
		
		// Validate if params is an array (can't put it in the function definition as it's extending a method)
		if ( !is_array($params))
		{
			return false;
		}
		
		// Validate format of OpenID identity
		if ( !Zend_OpenId::normalize($id)) {
			return false;
		}
		
		// Get information about current visitor
		$visitor = XenForo_Visitor::getInstance();
		
		// Don't allow logging in with unconfirmed users
		if ($visitor->user_state != 'valid')
		{
			XenForo_Error::debug('%s',__CLASS__.'::'.__METHOD__.' - Visitor\'s user state not valid');
			return false;
		}
		
		// Validate if visitor username matches the one given in the identity
		if ( ! $visitor->username || $visitor->username != urldecode(basename($id))) // urldecode to allow for unicode characters
		{
			
			// If no authentication data was provided attempting a fresh login won't be possible
			if ( ! isset($params['authData']))
			{
				return false;
			}
			
			// Try to decrypt the authentication data and use it to login
			if ( ! $authData = XenSSO_Shared_Secure::decrypt($params['authData']))
			{
				XenForo_Error::logException(new Exception(__CLASS__.'::'.__METHOD__.' - Failed to decrypt authData'));
				return false;
			}
			
			if ( ! $this->attemptXenForoLogin($authData))
			{
				return false;
			}
			
		}
		
		// We're still here! Set the logged in user to the one provided
		$this->_user->setLoggedInUser($id);
		
		return true;
		
	}
	
	/**
	 * Attempt to login to XenForo using the authentication data provided
	 * 
	 * @param	array			$data
	 * 
	 * @return	bool							
	 */
	protected static function attemptXenForoLogin(array $data)
	{
		
		// Prepare DB models
		$loginModel = new XenForo_Model_Login();
		$userModel 	= new XenForo_Model_User();
		
		// Validate the authentication data
		$userId = $userModel->validateAuthentication($data['login'], $data['password'], $error);
		
		if ( ! $userId)
		{
			$loginModel->logLoginAttempt($data['login']);
			return false;
		}
		
		// Don't allow login for non-activated users
		$user = $userModel->getUserByNameOrEmail($data['login']);
		if ($user['user_state'] != 'valid')
		{
			XenForo_Error::debug('%s',__CLASS__.'::'.__METHOD__.' - Login failed, user state not valid');
			return false;
		}
		
		// If authentication data was valid we can clear previous login attempts
		$loginModel->clearLoginAttempts($data['login']);
		
		// Set "remember me" cookie if requested
		if ($data['remember'])
		{
			$userModel->setUserRememberCookie($userId);
		}
		
		// Log login
		XenForo_Model_Ip::log($userId, 'user', $userId, 'login');
		
		// Delete session activity to this point (we're starting a new one)
		$request = new Zend_Controller_Request_Http();
		$userModel->deleteSessionActivity(0, $request->getClientIp(false));
		
		// Get active session and update it with the newly logged in user id
		$session = XenForo_Application::get('session');
		$session->changeUserId($userId);
		
		// Set up visitor instance
		XenForo_Visitor::setup($userId);
		
		return true;
	}
	
    /**
     * Perepares information to send back to consumer's authentication request
     * and signs it using shared secret.
     *
     * @param float $version OpenID protcol version
     * @param array $ret arguments to be send back to consumer
     * @param array $params GET or POST request variables
     * @param mixed $extensions extension object or array of extensions objects
     * @return array
     */
    protected function _respond($version, $ret, $params, $extensions=null)
    {
		$result = parent::_respond($version, $ret, $params, $extensions);
		
		// Get active visitor
		$visitor = XenForo_Visitor::getInstance();
		
		if ($result['openid.mode'] == 'id_res' AND $visitor->user_id > 0)
		{
			// Set auth data to be returned
			$authModel = new XenSSO_Shared_Model_Auth;
			$authData  = $authModel->getSyncUserById($visitor->user_id);
			unset($authData['user_id']);
			$authData  = XenSSO_Shared_Secure::encrypt($authData);
			
			$result['authData'] = $authData;
		}
		
		return $result;
	}
	
	/**
	 * Capture and foward request to Zend_OpenId::redirect so the functionality can be extended
	 * 
	 * @param	string								$url			
	 * @param	array|null							$params			
	 * @param	Zend_Controller_Response_Abstract	$response		
	 * @param	String								$method
	 * 
	 * @return	Zend_OpenId::redirect
	 */
	protected function redirect($url, $params = null, Zend_Controller_Response_Abstract $response = null, $method = 'GET')
	{
		if ($url != $this->_trustUrl)
		{
			// If prompts are turned off, use the returnTo url as the login url and append openid.mode=cancel
			if (isset($params['prompts']) AND $params['prompts'] == 0 AND isset($params['openid.return_to']))
			{
				$noPromptUrl = $params['openid.return_to'];
				$noPromptUrl .= strpos($noPromptUrl, '?') ? '&' : '?';
				$noPromptUrl .= 'openid.mode=cancel';
				
				return Zend_OpenId::redirect($noPromptUrl);
			}
		}
		
		return Zend_OpenId::redirect($url, $params, $response, $method);
	}
	
}