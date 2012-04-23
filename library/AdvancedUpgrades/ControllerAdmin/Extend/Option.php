<?php

/**
 * Extend Option controller to add necessary code to apply terminology
 */
class AdvancedUpgrades_ControllerAdmin_Extend_Option extends XFCP_AdvancedUpgrades_ControllerAdmin_Extend_Option
{
	
	/**
	 * @var array	Terminology Phrases to handle
	 */
	protected $_termPhrases = array(
		'account_upgrade', 'account_upgrades', 'account_upgrade_confirm', 'account_upgrade_purchased',
		'active_user_upgrades', 'available_upgrades', 'no_account_upgrades_can_be_purchased_at_this_time',
		'purchased_upgrades'
	);
	
	/**
	 * Extend save action to apply the terminology (if applicable)
	 * 
	 * @return	parent::actionSave()							
	 */
	public function actionSave()
	{
		$result = parent::actionSave();
		
		$options = $this->_input->filterSingle('options', XenForo_Input::STRING);
		if ($options AND isset($options['auuTerminology']))
		{
			$this->applyTerminology();
		}
		
		return $result;
	}
	
	/**
	 * Apply the chosen terminology
	 * 
	 * @return	void							
	 */
	protected function applyTerminology()
	{
		// Get options the non-traditional way, as we don't want the cached (outdated) version
		$options 		= XenForo_Model::create('XenForo_Model_Option')->rebuildOptionCache();
		$options 		= new XenForo_Options($options);
		
		$defaultLanguage= $options->defaultLanguageId;
		
		$phraseModel 	= new XenForo_Model_Phrase;
		$phrases 		= $phraseModel->getPhrasesInLanguageByTitles( $this->_termPhrases, 0 );
		
		// Loop through phrases to be modified
		foreach ($phrases AS $phrase)
		{
			// Prepare data
			$data 					= $phrase;
			$data['language_id'] 	= $defaultLanguage;
			unset($data['phrase_id'], $data['version_id'], $data['version_string']);
			
			// Check for custom terminology
			if ($options->auuTerminology != 'default')
			{
				// Detect plural or singular
				if (stripos($phrase['phrase_text'], 'upgrades') !== false)
				{
					$replace = $options->auuTerminologyPlural;
				}
				else
				{
					$replace = $options->auuTerminologySingular;
				}
				
				// Replace phrase text
				$data['phrase_text'] 	= preg_replace('/(\s)?(?:account|user)?\s?upgrades?/i','$1' . $replace, $phrase['phrase_text']);
			}
			
			// Get existing phrase for default language (if any)
			$existing 	= $phraseModel->getPhraseInLanguageByTitle($phrase['title'], $defaultLanguage);
			$writer 	= XenForo_DataWriter::create('XenForo_DataWriter_Phrase');
			
			if ($existing)
			{
				$writer->setExistingData($existing['phrase_id']);
			}
	
			// Save
			$writer->bulkSet($data);
			$writer->updateVersionId();
			$writer->save();
		}
	}
	
}