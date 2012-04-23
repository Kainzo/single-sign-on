<?php

/**
 * Admin controller for advanced upgrades, used to show log and entry details
 */
class AdvancedUpgrades_ControllerAdmin_AdvancedUpgrades extends XenForo_ControllerAdmin_Abstract
{
	
	/**
	 * @var int	Log entries to show per page
	 */
	protected $perPage = 20;
	
	/**
	 * View log entries
	 * 
	 * @return	$this->responseView()							
	 */
	public function actionLog()
	{
		
		$inputData = (object) $this->_input->filter(array(
			'page' 			=> array(XenForo_Input::INT, array('default' => 1))
		));
		
		$fetchOptions = array(
			'limit' 	=> $this->perPage,
			'offset'	=> ($inputData->page * $this->perPage) - $this->perPage
		);
		
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$transactions = $upgradeModel->getTransactionLog($fetchOptions);
		
		return $this->responseView('XenForo_ViewAdmin_Base', 'advancedupgrades_transaction_log', array(
			'total' 		=> $upgradeModel->getTransactionLogCount(),
			'page' 			=> $inputData->page,
			'transactions'	=> $transactions
		));
		
	}
	
	/**
	 * View log entry
	 * 
	 * @return	$this->responseView()							
	 */
	public function actionView()
	{
		$idTransaction = $this->_input->filterSingle('id', XenForo_Input::INT);
		
		$upgradeModel 	= $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$transaction 	= $upgradeModel->getTransactionLogEntry($idTransaction);
		
		$transaction['transaction_details'] = unserialize($transaction['transaction_details']);
		
		return $this->responseView('XenForo_ViewAdmin_Base', 'advancedupgrades_transaction_log_view', array(
			'transaction' => $transaction
		));
	}
	
	/**
	 * View active upgrade entry
	 * 
	 * @return	$this->responseView()							
	 */
	public function actionViewActive()
	{
		$idRecord = $this->_input->filterSingle('id', XenForo_Input::INT);
		
		$upgradeModel 	= $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$record 	 	= $upgradeModel->getActiveUserUpgradeRecordById($idRecord);
		
		$record['extra'] = unserialize($record['extra']);
		
		return $this->responseView('XenForo_ViewAdmin_Base', 'advancedupgrades_active_record_view', array(
			'record' => $record
		));
	}
	
}