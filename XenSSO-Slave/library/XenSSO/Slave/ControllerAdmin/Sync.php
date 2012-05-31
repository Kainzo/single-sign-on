<?php

/**
 * Admin controller for the sync script
 */
class XenSSO_Slave_ControllerAdmin_Sync extends XenForo_ControllerAdmin_Abstract
{
	
	/**
	 * Show page explaining the purpose of the tool and a button to start the sync process
	 * 
	 * @return	$this->responseView()
	 */
	public function actionIndex()
	{
		return $this->responseView('XenForo_ViewAdmin_Base', 'xensso_user_sync_confirm');
	}
	
	/**
	 * Start the sync process, the actual process is initiated by javascript that's embedded on the following page
	 * 
	 * @return	$this->responseView()
	 */
	public function actionSync()
	{
		return $this->responseView('XenForo_ViewAdmin_Base', 'xensso_user_sync');
	}
	
	/**
	 * Process a sync batch,and parse the results to javascript
	 * 
	 * @return	XenForo_ControllerResponse_View
	 */
	public function actionSyncProcess()
	{
		// Get user input
		$offset = $this->_input->filterSingle('offset', XenForo_Input::INT);
		$limit 	= $this->_input->filterSingle('limit', XenForo_Input::INT);
		
		// Set offset and limit
		$offset = empty($offset) ? 0 : $offset;
		$limit  = empty($limit) ? 100 : $limit;
		
		// Prep models that we'll be using
		$userModel 	 = new XenForo_Model_User;
		$xenssoModel = new XenSSO_Shared_Model_Auth;
		
		// Get XF options
		$options 	= XenForo_Application::get('options');
		
		// Get syncable users and strip user_id from fields
		$users 		= $xenssoModel->getSyncUsers(array('validOnly' => true, 'offset' => $offset, 'limit' => $limit));
		$users 		= array_map(create_function('$u', 'unset($u["user_id"]); return $u;'), $users);
		
		// Set progress statistics
		$num 		= array($offset + count($users),$userModel->countTotalUsers());
		
		// Encrypt input data and Sync users to master and receive the result
		$inputData 	= XenSSO_Shared_Secure::encrypt($users, $options->XenSSOSlaveSecretPrivate);
		$result 	= XenSSO_Slave_Sync::httpRequest('syncMultiple', array('inputData' => $inputData));
		
		// Attempt to parse the results, if it can't be parsed assume the sync failed
		if ( ! $failed = json_decode($result) AND $result != '[]')
		{
			// Log this as an error, it should not happen
			XenForo_Error::logException(new Exception(__CLASS__.'::'.__METHOD__.' - Unexpected result: ' . var_export($result, true)), false);
			
			// Add all users that were meant to be synced to the "failed" array
			$failed = array(array('error' => 'Invalid Result Data', 'usernames' => array()));
			foreach ($users AS $user)
			{
				$failed[0]['usernames'][] = $user['username'];
			}
		}
		
		// Output to browser
		$this->outputProgress($num, $failed);
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	/**
	 * Output progress, sends the results to javascript in the parent window
	 * 
	 * @param	array			$num			
	 * @param	array			$failed
	 * 
	 * @return	void							
	 */
	protected function outputProgress($num, $failed)
	{
		echo '<script>';
		
		if (count($failed) > 0)
		{
			echo 'window.parent.XenSSO.updateFailed('.json_encode($failed).');';
		}
		
		echo 'window.parent.XenSSO.updateProgress('.$num[0].','.$num[1].');';
		
		echo '</script>';
	}
	
}