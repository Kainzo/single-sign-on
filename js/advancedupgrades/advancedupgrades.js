AdvancedUpgrades = new function()
{
	
	var $this = this;
	
	this.init = function()
	{
		$this.events.bind();
		$this.extendOverlay();
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
	
	this.extendOverlay = function()
	{
		
		var originalOverlay 	= XenForo.createOverlay;
		XenForo.createOverlay 	= function($trigger, templateHtml, extraOptions)
		{
			var overlay = originalOverlay.call(this, $trigger, templateHtml, extraOptions);
			var elem 	= overlay.getOverlay();
			
			overlay.onLoad = function()
			{
				var position= elem.position();
				elem.css('position', 'absolute');
				elem.position(position);
				elem.find(".button[type=reset]").removeAttr('disabled').removeClass('disabled');
			};
			
			overlay.onClose = function()
			{
				elem.find('form').trigger('reset');
			};
			
			return overlay;
		};
		
	};
	
	$(document).ready(this.init);
	
};