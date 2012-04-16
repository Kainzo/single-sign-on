<?php

class AdvancedUpgrades_RoutePrefix_Upgrades implements XenForo_Route_Interface
{

	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = explode('/', $routePath);
		return $router->getRouteMatch('AdvancedUpgrades_ControllerPublic_Upgrades', $action[0]);
	}

}