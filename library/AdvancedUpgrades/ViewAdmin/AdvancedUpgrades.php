<?php

class AdvancedUpgrades_ViewAdmin_AdvancedUpgrades extends XenForo_ViewAdmin_Base
{
	
	protected $_renderJson = true;
	
	public function renderJson()
	{
		if ($this->_renderJson == false)
		{
			return null;
		}
		
		$this->_renderJson = false;
		$output = $this->_renderer->getDefaultOutputArray(__CLASS__, $this->_params, $this->_templateName);
		$this->_renderJson = true;
		
		array_walk_recursive($output, array($this, '_ensureUtf8'));
		
		return $output;
	}
	
	protected function _ensureUtf8($key, &$value)
	{
		if ( ! mb_check_encoding($value, 'UTF-8'))
		{
			$value = utf8_encode($value);
		}
	}
	
}