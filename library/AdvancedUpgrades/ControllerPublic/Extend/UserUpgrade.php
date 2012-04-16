<?php

class AdvancedUpgrades_ControllerPublic_Extend_UserUpgrade extends XFCP_AdvancedUpgrades_ControllerPublic_Extend_UserUpgrade
{

	/**
	 * Displays a form to add a user upgrade.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		return $this->_getUpgradeAddEditResponse(array(
			'user_upgrade_id' => null,
			'title' => '',
			'description' => '',
			'redirect' => '',
			'agreement'	=> '',
			'display_order' => 1,
			'extra_group_ids' => '',
			'recurring' => 0,
			'cost_amount' => 5,
			'cost_currency' => 'usd',
			'length_amount' => 1,
			'length_unit' => 'month',
			'disabled_upgrade_ids' => '',
			'can_purchase' => 1,
			'purchase_multiple' => 0
		));
	}
	
	/**
	 * Inserts a new upgrade or updates an existing one.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSave()
	{
		$this->_assertPostOnly();

		$userUpgradeId = $this->_input->filterSingle('user_upgrade_id', XenForo_Input::UINT);
		$input = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'redirect' => XenForo_Input::STRING,
			'agreement' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'extra_group_ids' => array(XenForo_Input::UINT, 'array' => true),
			'recurring' => XenForo_Input::UINT,
			'cost_amount' => XenForo_Input::UNUM,
			'cost_currency' => XenForo_Input::STRING,
			'length_amount' => XenForo_Input::UINT,
			'length_unit' => XenForo_Input::STRING,
			'disabled_upgrade_ids' => array(XenForo_Input::UINT, 'array' => true),
			'can_purchase' => XenForo_Input::UINT,
			'purchase_multiple'	=> XenForo_Input::UINT
		));
		if ($this->_input->filterSingle('length_type', XenForo_Input::STRING) == 'permanent')
		{
			$input['length_amount'] = 0;
			$input['length_unit'] = '';
		}

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_UserUpgrade');
		if ($userUpgradeId)
		{
			$dw->setExistingData($userUpgradeId);
		}
		$dw->bulkSet($input);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('user-upgrades') . $this->getLastHash($dw->get('user_upgrade_id'))
		);
	}
	
}