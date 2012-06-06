<?php

/**
 * OpenID Consumer
 * extends the Zend OpenID consumer to support sending extra parameters
 */
class XenSSO_Slave_OpenID_Consumer extends Zend_OpenId_Consumer
{

	protected $_extraParams = array();
	protected $_session;

    /**
     * Performs check of OpenID identity.
     *
     * This is the first step of OpenID authentication process.
     * On success the function does not return (it does HTTP redirection to
     * server and exits). On failure it returns false.
     *
     * @param bool $immediate enables or disables interaction with user
     * @param string $id OpenID identity
     * @param string $returnTo HTTP URL to redirect response from server to
     * @param string $root HTTP URL to identify consumer on server
     * @param mixed $extensions extension object or array of extensions objects
     * @param Zend_Controller_Response_Abstract $response an optional response
     *  object to perform HTTP or HTML form redirection
     * @return bool
     */
    protected function _checkId($immediate, $id, $returnTo=null, $root=null,
        $extensions=null, Zend_Controller_Response_Abstract $response = null)
    {
        $this->_setError('');

        if (!Zend_OpenId::normalize($id)) {
            $this->_setError("Normalisation failed");
            return false;
        }
        $claimedId = $id;

        if (!$this->_discovery($id, $server, $version)) {
            $this->_setError("Discovery failed: " . $this->getError());
            return false;
        }
        if (!$this->_associate($server, $version)) {
            $this->_setError("Association failed: " . $this->getError());
            return false;
        }
        if (!$this->_getAssociation(
                $server,
                $handle,
                $macFunc,
                $secret,
                $expires)) {
            /* Use dumb mode */
            unset($handle);
            unset($macFunc);
            unset($secret);
            unset($expires);
        }

        $params = array();
        if ($version >= 2.0) {
            $params['openid.ns'] = Zend_OpenId::NS_2_0;
        }

        $params['openid.mode'] = $immediate ?
            'checkid_immediate' : 'checkid_setup';

        $params['openid.identity'] = $id;

        $params['openid.claimed_id'] = $claimedId;

        if ($version <= 2.0) {
            if ($this->_session !== null) {
                $this->_session->set('identity', $id);
                $this->_session->set('claimed_id', $claimedId);
            } else {
                $this->_session = XenForo_Application::get('session');
                $this->_session->set('identity', $id);
                $this->_session->set('claimed_id', $claimedId);
            }
        }

        if (isset($handle)) {
            $params['openid.assoc_handle'] = $handle;
        }

        $params['openid.return_to'] = Zend_OpenId::absoluteUrl($returnTo);

        if (empty($root)) {
            $root = Zend_OpenId::selfUrl();
            if ($root[strlen($root)-1] != '/') {
                $root = dirname($root);
            }
        }
        if ($version >= 2.0) {
            $params['openid.realm'] = $root;
        } else {
            $params['openid.trust_root'] = $root;
        }

        if (!Zend_OpenId_Extension::forAll($extensions, 'prepareRequest', $params)) {
            $this->_setError("Extension::prepareRequest failure");
            return false;
        }

        $params = array_merge($params, $this->_extraParams);

        Zend_OpenId::redirect($server, $params, $response);
        return true;
    }
	
	/**
	 * Set additional parameters to send long with OpenID queries
	 * 
	 * @param	array			$params
	 * 
	 * @return	void							
	 */
	public function setParams(array $params)
	{
		$this->_extraParams = $params;
	}
	
    /**
     * Extending from main Zend OpenID Consumer to work around a bug in the Zend version
     * which raises an E_NOTICE when assoc_type is not set.
     *
     * @param string $url OpenID server url
     * @param string $method HTTP request method 'GET' or 'POST'
     * @param array $params additional qwery parameters to be passed with
     * @param int &$staus HTTP status code
     *  request
     * @return mixed
     */
    protected function _httpRequest($url, $method = 'GET', array $params = array(), &$status = null)
    {
		$url = str_replace(' ', '%20', $url); // replace spaces as Zend doesn't seem to recognize them as "valid"
		
		$result = parent::_httpRequest($url, $method, $params, $status);
		
		if ($result == false OR stripos($result, 'assoc_type') !== false)
		{
			return $result;
		}
		
		$result .= "\n" . "assoc_type:0";
		
		return $result;
	}

}