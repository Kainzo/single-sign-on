<?php

class AdvancedUpgrades_ControllerPublic_Upgrades extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');
		
		$visitor = XenForo_Visitor::getInstance();
		$purchaseList = $upgradeModel->getUpgradesForPurchaseList();
		
		if ( ! $purchaseList['available'])
		{
			return $this->responseMessage(new XenForo_Phrase('no_account_upgrades_can_be_purchased_at_this_time'));
		}

		$viewParams = array(
			'available' => $upgradeModel->prepareUserUpgrades($purchaseList['available']),
			'purchased' => $upgradeModel->prepareUserUpgrades($purchaseList['purchased'])
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'account_upgrades_advanced', $viewParams);
	}
	
	public function actionPurchase()
	{
		$visitor = XenForo_Visitor::getInstance();
		if ($visitor->user_id == 0)
		{
			return $this->purchaseGuest();
		}
		
		$upgrade 		= $this->getRequestedUpgrade();
		$upgradeId 		= $upgrade['user_upgrade_id'];
		
		$viewParams = array(
			'upgrade'	=> $upgrade,
			'payPalUrl' => 'https://www.paypal.com/cgi-bin/websrc'
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'account_upgrades_advanced_confirm', $viewParams);
	}
	
	public function actionPurchaseRegister()
	{
		
		$options 	= XenForo_Application::get('options');
		$writer 	= XenForo_DataWriter::create('XenForo_DataWriter_User');
		
		$data = $this->_input->filter(array(
			'username'   => XenForo_Input::STRING,
			'email'      => XenForo_Input::STRING,
		));
		$upgradeId 	= $this->_input->filterSingle('upgradeId', XenForo_Input::INT);
		$password 	= $this->_input->filterSingle('password', XenForo_Input::STRING);
		
		// Set registration details
		if ($options->registrationDefaults)
		{
			$writer->bulkSet($options->registrationDefaults, array('ignoreInvalidFields' => true));
		}
		
		// Set registration data
		$data['user_group_id'] 	= XenForo_Model_User::$defaultRegisteredGroupId;
		$data['language_id']	= XenForo_Visitor::getInstance()->get('language_id');
		
		$writer->bulkSet($data);
		$writer->setPassword($password, $password);
		
		$writer->advanceRegistrationUserState();
		$writer->preSave();
		
		if ($errors = $writer->getErrors())
		{
			return $this->responseError($errors); 
		}
		
		$writer->save();
		
		$user = $writer->getMergedData();
		
		// Log login
		XenForo_Model_Ip::log($user['user_id'], 'user', $user['user_id'], 'login');
		
		// Delete session activity to this point (we're starting a new one)
		$userModel = new XenForo_Model_User;
		$userModel->deleteSessionActivity(0, $this->_request->getClientIp(false));
		
		// Get active session and update it with the newly logged in user id
		$session = XenForo_Application::get('session');
		$session->changeUserId($user['user_id']);
		
		// Set up visitor instance
		XenForo_Visitor::setup($user['user_id']);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('upgrades/purchaseRedirect', null, array('upgradeId' => $upgradeId))
		);
	}
	
	public function actionPurchaseRedirect()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$upgrade 		= $this->getRequestedUpgrade();
		
		if ($upgrade['length_unit'] AND $upgrade['recurring'])
		{
			$params = array(
				'cmd'		=> '_xclick-subscriptions',
				'a3'		=> $upgrade['cost_amount'],
				'p3'		=> $upgrade['length_amount'],
				't3'		=> $upgrade['lengthUnitPP'],
				'src'		=> 1,
				'sra'		=> 1
			);
		}
		else
		{
			$params = array(
				'cmd'		=> '_xclick',
				'amount'	=> $upgrade['cost_amount']
			);
		}
		
		if ( ! empty($upgrade['redirect']))
		{
			$redirect = XenForo_Link::buildPublicLink('full:upgrades/purchasedRedirect', null, array('upgradeId' => $upgrade['user_upgrade_id']));
		}
		else
		{
			$redirect = XenForo_Link::buildPublicLink('full:account/upgrade-purchase');
		}
		
		$params 	= XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http);
		$baseUrl 	= $params['fullBasePath'];
		
		$params = array_merge($params,array(
			'business'		=> $options->payPalPrimaryAccount,
			'currency_code'	=> $upgrade['currency'],
			'item_name'		=> $upgrade['title'],
			'quantity'		=> 1,
			'no_note'		=> 1,
			'no_shipping'	=> 1,
			'custom'		=> implode(',',array($visitor->user_id,$upgrade['user_upgrade_id'],'token',$visitor->csrf_token_page)),
			'charset'		=> 'utf-8',
			'email'			=> $visitor->email,
			'return'		=> $redirect,
			'cancel_return'	=> XenForo_Link::buildPublicLink('full:upgrades'),
			'notify_url'	=> $baseUrl . 'payment_callback.php'
		));
		
		$url = 'https://www.paypal.com/cgi-bin/websrc?' . http_build_query($params);
		
		@header('location: ' . $url);
		echo '<meta http-equiv="refresh" content="0; url='.$url.'/">';
		
		// Give XenForo what it wants
		$this->_routeMatch->setResponseType('raw');
		return new XenForo_ControllerResponse_View( '' );
	}
	
	public function actionPurchasedRedirect()
	{
		$upgradeId 		= $this->_input->filterSingle('upgradeId', XenForo_Input::INT);
		$upgradeModel 	= $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$upgrade 		= $upgradeModel->getUserUpgradeById($upgradeId);
		
		if ( ! $upgrade)
		{
			throw new XenForo_Exception(new XenForo_Phrase('requested_user_upgrade_not_found'), true);
		}
		
		$redirect = $upgrade['redirect'];
		
		if (substr($redirect,0,1) == '/' OR substr($redirect,0,4) == 'http')
		{
			@header("location: " . $redirect);
			echo '<meta http-equiv="refresh" content="0;url='.$redirect.'" />';
			
			// Give XenForo what it wants
			$this->_routeMatch->setResponseType('raw');
			return new XenForo_ControllerResponse_View( '' );
		}
		else
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink($redirect)
			);
		}
	}
	
	public function actionAgreement()
	{
		$upgradeId 		= $this->_input->filterSingle('upgradeId', XenForo_Input::INT);
		$upgradeModel 	= $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$upgrade 		= $upgradeModel->getUserUpgradeById($upgradeId);
		
		if ( ! $upgrade)
		{
			throw new XenForo_Exception(new XenForo_Phrase('requested_user_upgrade_not_found'), true);
		}
		
		return $this->responseView('XenForo_ViewPublic_Base', 'account_upgrade_agreement', array('upgrade' => $upgrade));
	}
	
	/**
	 * Disable CSRF checking
	 */
	protected function _checkCsrf($action)
	{
		if (strtolower($action) == 'actionpurchasedredirect')
		{
			return;
		}

		parent::_checkCsrf($action);
	}
	
	private function purchaseGuest()
	{
		$upgrade 		= $this->getRequestedUpgrade();
		$upgradeId 		= $upgrade['user_upgrade_id'];
		
		$upgradeModel 	= $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$purchaseList 	= $upgradeModel->getUpgradesForPurchaseList();
		
		if (!$purchaseList['available'])
		{
			return $this->responseMessage(new XenForo_Phrase('no_account_upgrades_can_be_purchased_at_this_time'));
		}
		
		$upgrade = false;
		foreach ($purchaseList['available'] AS $purchase)
		{
			if ($purchase['user_upgrade_id'] == $upgradeId)
			{
				$upgrade = $purchase;
			}
		}
		
		$viewParams = array(
			'captcha' => XenForo_Captcha_Abstract::createDefault(),
			'tosUrl' => XenForo_Dependencies_Public::getTosUrl(),
			
			'upgrade'	=> $upgrade,
			'payPalUrl' => 'https://www.paypal.com/cgi-bin/websrc'
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'account_upgrades_advanced_register', $viewParams);
	}
	
	private function getRequestedUpgrade()
	{
		if ( ! $upgradeId = $this->_input->filterSingle('upgradeId', XenForo_Input::INT))
		{
			throw new XenForo_Exception(new XenForo_Phrase('requested_user_upgrade_not_found'), true);
		}
		
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$purchaseList = $upgradeModel->getUpgradesForPurchaseList();
		
		if (!$purchaseList['available'])
		{
			throw new XenForo_Exception(new XenForo_Phrase('no_account_upgrades_can_be_purchased_at_this_time'), true);
		}
		
		$upgrade = false;
		foreach ($purchaseList['available'] AS $purchase)
		{
			if ($purchase['user_upgrade_id'] == $upgradeId)
			{
				$upgrade = $purchase;
			}
		}
		
		if ($upgrade == false)
		{
			throw new XenForo_Exception(new XenForo_Phrase('requested_user_upgrade_not_found'), true);
		}
		
		return $upgradeModel->prepareUserUpgrade($upgrade);
	}
	
}