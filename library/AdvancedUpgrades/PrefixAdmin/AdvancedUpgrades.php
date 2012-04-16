<?php

class AdvancedUpgrades_PrefixAdmin_AdvancedUpgrades implements XenForo_Route_Interface
{
	
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('AdvancedUpgrades_ControllerAdmin_AdvancedUpgrades', $routePath, 'users');
	}
	
}