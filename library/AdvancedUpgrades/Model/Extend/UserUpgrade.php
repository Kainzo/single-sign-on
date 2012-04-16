<?php

class AdvancedUpgrades_Model_Extend_UserUpgrade extends XFCP_AdvancedUpgrades_Model_Extend_UserUpgrade
{
	
	/**
	 * Gets a list of available upgrades 
	 *
	 * @return array
	 * 		[available] -> list of upgrades that can be purchased,
	 * 		[purchased] -> list of purchased, with [record] key inside for specific info
	 */
	public function getUpgradesForPurchaseList()
	{
		$purchased = array();
		$upgrades = array();

		$this->standardizeViewingUserReference($viewingUser);
		
		if ($viewingUser['user_id'])
		{
			return $this->getUserUpgradesForPurchaseList($viewingUser);
		}
		
		if ($upgrades = $this->getAllUserUpgrades())
		{

			foreach ($upgrades AS $upgradeId => $upgrade)
			{
				// remove any upgrades disabled by this
				if ($upgrade['disabled_upgrade_ids'])
				{
					foreach (explode(',', $upgrade['disabled_upgrade_ids']) AS $disabledId)
					{
						unset($upgrades[$disabledId]);
					}
				}
				else if (!$upgrade['can_purchase'])
				{
					unset($upgrades[$upgradeId]);
				}
			}
		}

		return array(
			'available' => $upgrades,
			'purchased' => $purchased
		);
	}
	
	/**
	 * Gets a list of upgrades that are applicable to the specified user.
	 *
	 * @param array|null $viewingUser
	 *
	 * @return array
	 * 		[available] -> list of upgrades that can be purchased,
	 * 		[purchased] -> list of purchased, with [record] key inside for specific info
	 */
	public function getUserUpgradesForPurchaseList(array $viewingUser = null)
	{
		$purchased = array();
		$upgrades = array();

		$this->standardizeViewingUserReference($viewingUser);
		if ($viewingUser['user_id'] && $upgrades = $this->getAllUserUpgrades())
		{
			$activeUpgrades = $this->getActiveUserUpgradeRecordsForUser($viewingUser['user_id']);

			foreach ($upgrades AS $upgradeId => $upgrade)
			{
				if (isset($activeUpgrades[$upgradeId]))
				{
					// purchased
					$purchased[$upgradeId] = $upgrades[$upgradeId];
					$purchased[$upgradeId]['record'] = $activeUpgrades[$upgradeId];
					
					if ( ! $upgrade['purchase_multiple'])
					{
						unset($upgrades[$upgradeId]); // can't buy again
					}

					// remove any upgrades disabled by this
					if ($upgrade['disabled_upgrade_ids'])
					{
						foreach (explode(',', $upgrade['disabled_upgrade_ids']) AS $disabledId)
						{
							unset($upgrades[$disabledId]);
						}
					}
				}
				else if (!$upgrade['can_purchase'])
				{
					unset($upgrades[$upgradeId]);
				}
			}
		}

		return array(
			'available' => $upgrades,
			'purchased' => $purchased
		);
	}
	
	/**
	 * Upgrades the user with the specified upgrade.
	 *
	 * @param integer $userId
	 * @param array $upgrade Info about upgrade to apply
	 * @param boolean $allowInsertUnpurchasable Allow insert of a new upgrade even if not purchasable
	 * @param integer|null $endDate Forces a specific end date; if null, don't overwrite
	 *
	 * @return integer|false User upgrade record ID
	 */
	public function upgradeUser($userId, array $upgrade, $allowInsertUnpurchasable = false, $endDate = null)
	{
		$db = $this->_getDb();

		$active = $this->getActiveUserUpgradeRecord($userId, $upgrade['user_upgrade_id']);
		if ($active)
		{
			// updating an existing upgrade - if no end date override specified, extend the upgrade
			$activeExtra = unserialize($active['extra']);

			if ($endDate === null)
			{
				if ($active['end_date'] == 0 || !$activeExtra['length_unit'])
				{
					$endDate = 0;
				}
				else
				{
					$endDate = strtotime('+' . $activeExtra['length_amount'] . ' ' . $activeExtra['length_unit'], $active['end_date']);
				}
			}
			else
			{
				$endDate = intval($endDate);
			}

			$db->update('xf_user_upgrade_active',
				array('end_date' => $endDate, 'amount' => $active['amount'] + 1),
				'user_id = ' . $db->quote($userId) . ' AND user_upgrade_id = ' . $db->quote($upgrade['user_upgrade_id'])
			);

			return $active['user_upgrade_record_id'];
		}
		else
		{
			return parent::upgradeUser($userId,$upgrade,$allowInsertUnpurchasable,$endDate);
		}
	}
	
	public function getTransactionLog(array $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT *
				FROM xf_user_upgrade_log
				ORDER BY log_date DESC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_upgrade_log_id');
	}
	
	public function getTransactionLogEntry($idTransaction)
	{
		if (empty($idTransaction))
		{
			return false;
		}

		return $this->_getDb()->fetchRow('
			SELECT
				*
			FROM xf_user_upgrade_log
			WHERE user_upgrade_log_id = ?
		', $idTransaction);
	}
	
	public function getTransactionLogCount()
	{
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_user_upgrade_log
		');
	}
	
}