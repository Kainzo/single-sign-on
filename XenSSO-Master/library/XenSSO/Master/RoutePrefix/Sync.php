<?php

/**
 * Handle requests for the 'sync' prefix
 */
class XenSSO_Master_RoutePrefix_Sync implements XenForo_Route_Interface
{

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
		$action = explode('/', $routePath);
		return $router->getRouteMatch('XenSSO_Master_Controller_Sync', $action[0]);
	}

}