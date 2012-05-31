XenSSO = new function()
{
	
	var $this = this;
	
	var timer  = null;
	var paused = false;
	
	var progress = [0,100];
	
	this.init = function()
	{
		$this.events.bind();
	}
	
	this.events =
	{
		
		bind: function()
		{
			$("#xensso_pause").click($this.events.onPauseClicked);
		},
		
		onPauseClicked: function()
		{
			if (paused)
			{
				paused = false;
				$("#xensso_pause").text("pause")
				$this.nextBatch();
			}
			else
			{
				paused = true;
				clearTimeout(timer);
				$("#xensso_pause").text("resume")
			}
			
			return false;
		}
		
	};
	
	this.updateProgress = function(c,l)
	{
		if (c > l) c = l;
		
		progress = [c,l];
		
		$("#xensso_progress_pending").remove();
		$("#xensso_progress").text(c + "/" + l);
			
		if (c == l || paused)
		{
			$("#xensso_pause").parent().text("Progress");
			$("#xensso_progress").after($("<span>").text(" - All done!"));
			return;
		}
		
		setTimeout(function()
		{
			$this.nextBatch();
		},1000);		
	};
	
	this.updateFailed = function(failed)
	{
		$("#xensso_failed_empty").remove();
		
		var failed_count = $("#xensso_failed_count").text();
		failed_count = parseInt(failed_count);
		failed_count = isNaN(failed_count) ? 0 : failed_count;
		
		for (var error in failed)
		{
			failed_count += failed[error].usernames.length;
			window.parent.$("#xensso_failed_count").text(failed_count);
			
			if ($(".xensso_failed_error[rel=\'"+error+"\']").length == 0)
			{
				var node = $("<div class=xensso_failed_error>")
				node.attr("rel", error);
				node.append($("<a class=title href=#>").text(failed[error].error + ": "));
				node.append($("<span class=count>").text(0));
				node.append($("<span class=usernames>").hide());
				node.find("a").click(function() { XenForo.alert($(this).parent().find(".usernames").text(),"Usernames"); return false; });
				node.appendTo("#xensso_failed");
			}
			
			var e = $(".xensso_failed_error[rel=\'"+error+"\']");
			
			e.find(".count").text((parseInt(e.find(".count").text()) + failed[error].usernames.length));
			e.find(".usernames").append("\'" + failed[error].usernames.join("\',\' ") + "\', ");
		}
	};
	
	this.nextBatch = function()
	{
		if (progress[0] == progress[1]) return;
		$("#xensso_iframe").attr("src", "admin.php?xensso-sync/syncProcess&offset="+progress[0]+"&limit=100");
	}
	
	$(document).ready(this.init);
	
}