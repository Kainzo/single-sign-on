<?php

/**
 * Upgrades prefix handles
 */
class AdvancedUpgrades_RoutePrefix_Upgrades implements XenForo_Route_Interface
{

	/**
	 * Match prefix against class
	 * 
	 * @param	string							$routePath		
	 * @param	Zend_Controller_Request_Http	$request		
	 * @param	XenForo_Router					$router
	 * 
	 * @return	$router->getRouteMatch()
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = explode('/', $routePath);
		return $router->getRouteMatch('AdvancedUpgrades_ControllerPublic_Upgrades', $action[0]);
	}

}