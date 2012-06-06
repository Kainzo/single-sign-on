<?php

/**
 * Provider Controller, handles all requests related to the OpenID Provider
 */
class XenSSO_Master_Controller_Provider extends XenForo_ControllerPublic_Abstract
{

	/**
	 * Perform identity check
	 * 
	 * @return	XenForo_ControllerResponse_View							
	 */
	public function actionIdentity()
	{
		$identity = $this->getIdentity();
		
		echo '<html><head>
			<link rel="openid.server" href="'. XenForo_Link::buildPublicLink( 'full:sso/provider' ) .'" />
			</head><body>' . $identity  .'</body></html>';
			
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');	
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Main Provider controller, checks authentication and forwards calls as needed
	 * 
	 * @return	XenForo_ControllerResponse_View|XenForo_ControllerResponse_Error
	 */
	public function actionProvider()
	{
		$server 	= $this->getServer();
		$params 	= $this->getParams();
		
		// Check for failed login / invalid input
		if ( ! isset($params['openid_identity']) OR ! $server->login($params['openid_identity'], $params) )
		{
			
			// Only resort to error after 1 login attempt
			if (isset($params['tries']) AND $params['tries'] > 1)
			{
				$this->getParams(true); // called with true to reset session variables
				
				XenForo_Error::debug('%s',__CLASS__.'::'.__METHOD__.' - Login Failed, params: ' . var_export($params, true));
				
				if (isset($params['openid_return_to']))
				{
					Zend_OpenId::redirect( $params['openid_return_to'], array( 'openid.mode' => 'cancel' ) );
				}
				
				return $this->responseErrorRaw(new XenForo_Phrase('xensso_master_login_failed'));
			}
		}
		
		// Pass OpenID request to Provider, the end-user will be redirected to trust / login pages as needed
		// or redirected back to the consumer in case they are already logged in or there's something wrong with the request
		$ret = $server->handle($params, $this->getSreg());
		
		if (is_string($ret))
		{
			echo $ret;
		}
		else if ($ret !== true) // This really shouldn't happen
		{
			XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - 403 Forbidden, params: ' . var_export($params, true) ));
			
			if (isset($params['openid_return_to']))
			{
				Zend_OpenId::redirect( $params['openid_return_to'], array( 'openid.mode' => 'cancel' ) );
			}
			
			header('HTTP/1.0 403 Forbidden');
			echo 'Forbidden';
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');	
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Trust the referring site
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionTrust()
	{
		
		// Prepare variables 
		$server 	= $this->getServer();
		$params 	= $this->getParams(true);
		$siteRoot 	= $server->getSiteRoot($params);
		$domain 	= $this->stripDomain($siteRoot);
		
		// Check if the referring site is trustable
		if ( ! $this->siteIsTrustable($siteRoot))
		{
			XenForo_Error::logException(new Exception( __CLASS__.'::'.__METHOD__.' - Unrecognized domain: '.$domain ));
			Zend_OpenId::redirect( $params['openid_return_to'], array( 'openid.mode' => 'cancel' ) );
		}
		else
		{
			// It's trustable, store site and respond to consumer
			$username = urldecode(basename($server->getLoggedInUser()));
			$server->allowSite($siteRoot, $this->getSreg( $server->getLoggedInUser() ));
			$server->respondToConsumer($params, $this->getSreg( $server->getLoggedInUser() ) );
		}
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');	
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Check if given site is trustable (allowed to use this OpenID provider)
	 * 
	 * @param	string			$siteRoot		
	 * @return	bool							
	 */
	protected function siteIsTrustable($siteRoot)
	{
		
		// Retreive and parse the allowed domains
		$allowedDomains	= XenForo_Application::get('options')->XenSSOAllowedDomains;
		$allowedDomains	= explode("\n", $allowedDomains);
		$allowedDomains = array_map('trim', $allowedDomains);
		
		// Strip the domain from the input string
		$domain 		= $this->stripDomain( $siteRoot );
		
		// Check if the provided site's domain is an allowed domain and return the result
		return in_array( $domain, $allowedDomains );
		
	}
	
	/**
	 * Strip domain from the given url
	 * 
	 * @param	string			$url
	 * 
	 * @return	string							
	 */
	protected function stripDomain($url)
	{
		$url = parse_url($url);
		return $url['host'];
	}
	
	/**
	 * Get the identity for the current call
	 * 
	 * @return	string							
	 */
	protected function getIdentity()
	{
		$params = $this->getParams();
		
		// If the OpenID identity is set in the params, just return that
		if (isset($params['openid_identity']))
		{
			$identity 	= $params['openid_identity'];
		}
		else
		{
			// Use the Request URI as the identity
			$identity 	= Zend_OpenId::absoluteUrl($_SERVER['REQUEST_URI']);
		}
		
		return $identity;
	}
	
	/**
	 * Get instantiated sreg extension for current call
	 * 
	 * @param	string			$identity
	 * 
	 * @return	Zend_OpenId_Extension_Sreg
	 */
	protected function getSreg($identity = null)
	{
		// Set identity if not set
		if ($identity == null)
		{
			$identity = $this->getIdentity();
		}
		
		// Strip username from identity (urldecode it as it may contain unicode characters)
		$username = urldecode(basename($identity));
		
		// Get user from database
		$userModel = new XenForo_Model_User;
		$user = $userModel->getUserByNameOrEmail($username, array('join' => XenForo_Model_User::FETCH_USER_PROFILE));
		
		// Set date of birth 
		if (empty($user['dob_year']))
		{
			$dob = 'false';
		}
		else
		{
			$dob = $user['dob_day'].'/'.$user['dob_month'].'/'.$user['dob_year'];
		}
		
		$data = array(
			'nickname'	=> $user['username'],
			'email'		=> empty($user['email']) ? 'undefined@undefined.tld' : $user['email'],
			'dob'		=> $dob
		);
		
		return new Zend_OpenId_Extension_Sreg($data);
	}
	
	/**
	 * Get login URL
	 * 
	 * @return	string							
	 */
	protected function getLoginUrl()
	{
		return XenForo_Link::buildPublicLink(
			'login/index', null, 
			array( 
				'redirect' 	=> XenForo_Link::buildPublicLink( 'full:sso/provider' )
			)
		);
	}
	
	/**
	 * Get Provider server instance
	 * 
	 * @return	XenSSO_Master_OpenID_Provider_XenForo
	 */
	protected function getServer()
	{
		return new XenSSO_Master_OpenID_Provider_XenForo( 
			$this->getLoginUrl(), 
			XenForo_Link::buildPublicLink( 'full:sso/trust' ) 
		);
	}
	
	/**
	 * Get query params
	 * 
	 * @param	bool			$resetSession
	 * 
	 * @return	array							
	 */
	protected function getParams($resetSession = false)
	{
		$session = XenForo_Application::get('session');
		
		// Figure out where to get the params from (GET, POST or SESSION)
		if ($_SERVER["REQUEST_METHOD"] == "GET" AND isset($_GET['openid_mode'])) 
		{
			$result = $_GET;
		} 
		else if ($_SERVER["REQUEST_METHOD"] == "POST" AND isset($_POST['openid_mode'])) 
		{
			$result = $_POST;
		} 
		else if ($session->get('sso_params')) 
		{
			$result = unserialize($session->get('sso_params'));
		} 
		else 
		{
			return array();
		}
		
		// Set number of login attempts
		if ( !isset($result['tries']))
		{
			$result['tries'] = 0;
		}
		$result['tries']++;
		
		// Reset session variables if requested
		if ($resetSession)
		{
			$session->remove('sso_params');
		}
		else
		{
			$session->set('sso_params', serialize($result));
		}

		return $result;
	}

}