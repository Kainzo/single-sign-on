<?php

class XenSSO_Shared_Model_Auth extends XenForo_Model
{
	
	public function getSyncUsers(array $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT 	user.user_id, user.username, user.email,
						user_profile.dob_day, user_profile.dob_month, user_profile.dob_year, 
						user_authenticate.*, user_profile.facebook_auth_id
				FROM xf_user AS user
				JOIN xf_user_profile AS user_profile ON user.user_id=user_profile.user_id
				JOIN xf_user_authenticate AS user_authenticate ON user.user_id=user_authenticate.user_id
				WHERE user.user_state = \'valid\'
				ORDER BY user.user_id ASC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}
	
	public function getSyncUserById($userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT 	user.user_id, user.username, user.email, user.user_state,
					user_profile.dob_day, user_profile.dob_month, user_profile.dob_year, 
					user_authenticate.*, user_profile.facebook_auth_id
			FROM xf_user AS user
			JOIN xf_user_profile AS user_profile ON user.user_id=user_profile.user_id
			JOIN xf_user_authenticate AS user_authenticate ON user.user_id=user_authenticate.user_id
			WHERE user.user_id = ?
			LIMIT 1
		', $userId);
	}
	
}