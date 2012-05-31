<?php

/**
 * Route handler for prefix 'sync-slave'
 */
class XenSSO_Slave_RoutePrefix_Sync implements XenForo_Route_Interface
{

	/**
	 * Match to action
	 * 
	 * @param	string							$routePath		
	 * @param	Zend_Controller_Request_Http	$request		
	 * @param	XenForo_Router					$router
	 * 
	 * @return	$router->getRouteMatch
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = explode('/', $routePath);
		return $router->getRouteMatch('XenSSO_Slave_Controller_Sync', $action[0]);
	}

}