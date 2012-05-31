<?php

/**
 * Extend AccountConfirmation controller to catch succesful confirmations and sync them to master
 */
class XenSSO_Slave_Controller_Extend_AccountConfirmation extends XFCP_XenSSO_Slave_Controller_Extend_AccountConfirmation
{
	
	/**
	* Controller response for when you want to output using a view class.
	* Extended to check for succesful account confirmation.
	*
	* @param string Name of the view class to be rendered
	* @param string Name of the template that should be displayed (may be ignored by view)
	* @param array  Key-value pairs of parameters to pass to the view
	* @param array  Key-value pairs of parameters to pass to the container view
	*
	* @return XenForo_ControllerResponse_View
	*/
	public function responseView($viewName, $templateName = 'DEFAULT', array $params = array(), array $containerParams = array())
	{
		if (
			$viewName == 'XenForo_ViewPublic_Register_Confirm' AND
			$templateName == 'register_confirm' AND
			isset($params['user']) 
		)
		{
			XenSSO_Slave_Sync::activateAccount($params['user']);
		}
		
		return parent::responseView($viewName, $templateName, $params, $containerParams);
	}
	
}