<?php

/**
 * Synchronisation controllers
 */
class XenSSO_Slave_Controller_Sync extends XenForo_ControllerPublic_Abstract
{
	
	/**
	 * Controller called when the user has logged in on the provider using an auth key
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionKeySuccess()
	{
		echo 'success';
		
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Controller called with data that is to be interpreted with javascript (called from inside iframe)
	 * 
	 * @return	string
	 */
	public function actionJsCallback()
	{
		$data = $this->_input->filterSingle('data', XenForo_Input::STRING);
		echo $data;
		
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}

}