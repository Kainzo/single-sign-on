<?php

class XenSSO_Slave_PrefixAdmin_Sync implements XenForo_Route_Interface
{
	
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('XenSSO_Slave_ControllerAdmin_Sync', $routePath, 'tools');
	}
	
}