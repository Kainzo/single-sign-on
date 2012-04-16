AdvancedUpgrades = new function()
{
	
	var $this = this;
	
	this.init = function()
	{
		$this.events.bind();
	};
	
	this.events = {
		
		bind: function()
		{
			$("form.advancedUpgrade").live('AutoValidationDataReceived', $this.events.onValidationDataReceived)
		},
		
		onValidationDataReceived: function(eDataRecv)
		{
			if (eDataRecv.ajaxData.error != undefined)
			{
				$(this).find(".errorPanel").hide();
				$(this).find(".errorPanel ol").empty();
				for (var e in eDataRecv.ajaxData.error)
				{
					$(this).find(".errorPanel ol").append($("<li>").text(eDataRecv.ajaxData.error[e]));
				}
				$(this).find(".errorPanel").slideDown();
			}
			else if (eDataRecv.ajaxData._redirectTarget != undefined)
			{
				window.location = XenForo.canonicalizeUrl(eDataRecv.ajaxData._redirectTarget);
			}
			else
			{
				return;
			}
			
			eDataRecv.preventDefault();
		}
		
	};
	
	$(document).ready(this.init);
	
};