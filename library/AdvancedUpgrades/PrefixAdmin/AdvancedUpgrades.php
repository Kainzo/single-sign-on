<?php

/**
 * Admin Upgrades prefix handler
 */
class AdvancedUpgrades_PrefixAdmin_AdvancedUpgrades implements XenForo_Route_Interface
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
		return $router->getRouteMatch('AdvancedUpgrades_ControllerAdmin_AdvancedUpgrades', $routePath, 'users');
	}
	
}