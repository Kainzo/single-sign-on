<?php

/**
 * Admin controller for advanced upgrades, used to show log and entry details
 */
class AdvancedUpgrades_ControllerAdmin_AdvancedUpgrades extends XenForo_ControllerAdmin_Abstract
{
	
	/**
	 * @var int	Log entries to show per page
	 */
	protected $perPage = 5;
	
	/**
	 * View log entries
	 * 
	 * @return	$this->responseView()							
	 */
	public function actionLog()
	{
		
		$criteria = $this->_input->filterSingle('criteria', XenForo_Input::ARRAY_SIMPLE);
		
		$filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
		if ($filter && isset($filter['value']))
		{
			$criteria['search'] = $filter['value'] . '%';
			
			if ($filter['prefix'] != true)
			{
				$criteria['search'] = '%' . $criteria['search'];
			}
			
			$filterView = true;
		}
		else
		{
			$filterView = false;
		}

		$order = $this->_input->filterSingle('order', XenForo_Input::STRING);
		$direction = $this->_input->filterSingle('direction', XenForo_Input::STRING);
		
		$inputData = (object) $this->_input->filter(array(
			'page' 			=> array(XenForo_Input::INT, array('default' => 1))
		));
		
		$fetchOptions = array(
			'limit' 	=> $this->perPage,
			'offset'	=> ($inputData->page * $this->perPage) - $this->perPage,

			'order' => $order,
			'direction' => $direction
		);
		
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');
		$transactions = $upgradeModel->getTransactionLog($criteria, $fetchOptions);
		
		$view = $filterView ? 'advancedupgrades_transaction_log_items' : 'advancedupgrades_transaction_log';
		
		return $this->responseView('AdvancedUpgrades_View_AdvancedUpgrades', $view, array(
			'linkParams' 	=> array('criteria' => $criteria, 'order' => $order, 'direction' => $direction),
			'total' 		=> $upgradeModel->getTransactionLogCount(),
			'page' 			=> $inputData->page,
			'perPage'		=> $this->perPage,
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
		
		return $this->responseView('AdvancedUpgrades_View_AdvancedUpgrades', 'advancedupgrades_transaction_log_view', array(
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
		
		return $this->responseView('AdvancedUpgrades_View_AdvancedUpgrades', 'advancedupgrades_active_record_view', array(
			'record' => $record
		));
	}
	
}