var XenSSO_Slave = new function()
{
	
	var $this = this;

	this.init = function() 
	{
		$this.attemptLogin();
		$this.forwardSession();
	};
	
	this.attemptLogin = function()
	{
		if (typeof xensso_attempt_login === 'undefined')
		{
			return;
		}
		
		var redirect = encodeURIComponent( $("base").attr("href") + "index.php?sync-slave/jsCallback&data=" );
		$this.loadUrl(xensso_master_url + "index.php?sync/myIdentity&redirect= " + redirect, function(identity) {
			$this.loadUrl($("base").attr("href") + "index.php?sso-slave/login&openid_identity=" + encodeURIComponent(identity), function() {
				window.location.reload();
			});
		});
	};

	this.forwardSession = function()
	{
		if (typeof xensso_auth_data === 'undefined')
		{
			return;
		}

		var redirect = $("base").attr("href") + "sync-slave/keySuccess";

		$this.loadUrl(xensso_master_url + "index.php?sync/key&authData=" + encodeURIComponent(xensso_auth_data) + "&redirect=" + encodeURIComponent(redirect));
	};

	this.loadUrl = function(url, callback)
	{
		$("#xensso_frame").remove();

		var iframe = $("<iframe>");
		iframe.attr("id", "xensso_frame");
		iframe.css({width: 1, height: 1});
		iframe.attr("src", url);

		iframe.appendTo("body");
		
		setTimeout(iframe.hide,100);
		
		iframe.load(function()
		{
			if (typeof callback != 'undefined')
			{
				callback($(this).contents().find("body").text());
			}
		});
	};

	$(document).ready(this.init);

};