<?php

/**
 * Extend registration controller to catch succesful user signups so we can sync them to the master
 */
class XenSSO_Slave_Controller_Extend_Register extends XFCP_XenSSO_Slave_Controller_Extend_Register
{
	
	/**
	 * Registers a new account (or associates with an existing one) using Facebook.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFacebookRegister()
	{
		$this->_assertPostOnly();
		
		$userModel = $this->_getUserModel();
		
		$doAssoc = (
			$this->_input->filterSingle('associate', XenForo_Input::STRING) OR
			$this->_input->filterSingle('force_assoc', XenForo_Input::UINT)
		);
		
		if ($doAssoc)
		{
			$associate = $this->_input->filter(array(
				'associate_login' => XenForo_Input::STRING,
				'associate_password' => XenForo_Input::STRING
			));
			
			$userId = $userModel->validateAuthentication($associate['associate_login'], $associate['associate_password'], $error);
			if ( ! $userId)
			{
				$user 	= XenSSO_Slave_Sync::copyFromMaster($associate['associate_login']);
				$fbUser = XenSSO_Slave_Sync::getFbUser();
				
				if ($user AND $fbUser AND $user['facebook_auth_id'] == $fbUser['id'])
				{
					XenForo_Helper_Facebook::setUidCookie($fbUser['id']);
		
					$redirect = XenForo_Application::get('session')->get('fbRedirect');
					XenForo_Application::get('session')->changeUserId($user['user_id']);
					XenForo_Visitor::setup($user['user_id']);
		
					XenForo_Application::get('session')->remove('fbRedirect');
					$redirect = $this->getDynamicRedirect(false, false);
		
					return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						$redirect
					);
				}
			}
		}
		
		return parent::actionFacebookRegister();
	}

	/**
	 * Catch response and check if it's for a succesful signup, if so sync it to the master
	 * 
	 * @param string Name of the view class to be rendered
	 * @param string Name of the template that should be displayed (may be ignored by view)
	 * @param array  Key-value pairs of parameters to pass to the view
	 * @param array  Key-value pairs of parameters to pass to the container view
	 * 
	 * @return	parent::responseView								
	 */
	public function responseView($viewName, $templateName = 'DEFAULT', array $params = array(), array $containerParams = array())
	{
		// Check if this is a succesful signup
		if ($viewName == 'XenForo_ViewPublic_Register_Process' AND isset($params['user']))
		{
			// sync to master
			XenSSO_Slave_Sync::copyToMaster($params['user']['user_id']);
		}
		
		return parent::responseView($viewName, $templateName, $params, $containerParams);
	}

}