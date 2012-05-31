<?php

/**
 * Handle requests for the 'sso' prefix
 */
class XenSSO_Master_RoutePrefix_Provider implements XenForo_Route_Interface
{
	
	// Only forward these actions
	protected $_actions = array('trust', 'provider');

	/**
	 * Match route against controller and method
	 * 
	 * @param	string							$routePath		
	 * @param	Zend_Controller_Request_Http	$request		
	 * @param	XenForo_Router					$router
	 * 
	 * @return	XenForo_RouteMatch
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		// parse route and extract action
		$action = explode('/', $routePath);
		$action = $action[0];
		
		// If the action is not recognized, use the 'identity' action
		if ( ! in_array($action, $this->_actions))
		{
			$action = 'identity';
		}
		
		// Return route
		return $router->getRouteMatch('XenSSO_Master_Controller_Provider', $action);
	}

}