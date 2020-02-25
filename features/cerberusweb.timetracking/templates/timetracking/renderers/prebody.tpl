{* begin time tracking *}
<style type="text/css">
#divTimeTrackingBox {
	position:fixed;
	top:5px;
	right:5px;
	opacity:.9;
	filter:alpha(opacity=90);
	z-index:2;
	vertical-align:middle;
	text-align:center;
	font-size:16pt;
	padding:8px;
	
	color:rgb(255,255,255);
	background-color:rgb(50,50,50);
	background: linear-gradient(top, rgb(100,100,100), rgb(50,50,50));
	background: -webkit-gradient(linear, left top, left bottom, from(rgb(100,100,100)), to(rgb(50,50,50)));
	background: -moz-linear-gradient(top, rgb(100,100,100), rgb(50,50,50));
	background: -o-linear-gradient(top, rgb(100,100,100), rgb(50,50,50));
	background: -ms-linear-gradient(top, rgb(100,100,100), rgb(50,50,50));
	
	-moz-box-shadow: 0 5px 15px 0px rgb(175,175,175);
	-webkit-box-shadow: 0 5px 15px 0px rgb(175,175,175);
	box-shadow: 0 5px 15px 0px rgb(175,175,175);
	
	border-radius:5px;
	-moz-border-radius:5px;
	-webkit-border-radius:5px;
}
#divTimeTrackingBox BUTTON {
	border:0;	
}
#divTimeTrackingCounter {
	font-size:16pt;
	font-weight:bold;
	margin-right:10px;
}
</style>
<div id="divTimeTrackingBox" style="display:{if isset($session.timetracking)}block{else}none{/if};">
	<div style="float:right;">
		<button id="btnTimeTrackingPlay" type="button" onclick="timeTrackingTimer.play();" style="display:none;"><span class="glyphicons glyphicons-play" style="color:rgb(0,180,0);"></span></button>
		<button id="btnTimeTrackingPause" type="button" onclick="timeTrackingTimer.pause();" style="display:none;"><span class="glyphicons glyphicons-pause"></span></button>
		<button id="btnTimeTrackingStop" type="button" onclick="timeTrackingTimer.stop();" style="display:none;"><span class="glyphicons glyphicons-stop" style="color:rgb(200,0,0);"></span></button>
	</div>
	<div style="float:left;">
		Time Spent: 
		<span id="divTimeTrackingCounter">--</span>
	</div>
</div>
<script type="text/javascript">
var timeTrackingTimerClass = function() {
	this.counter = 0;
	this.enabled = false;

	this.increment = function() {
		if(!this.enabled) return;

		++this.counter;
		this.redraw();	
		
		if(this.enabled) {
			var _self = this;
			setTimeout(function(ms) {
				_self.increment();
			} ,1000);
		}
	}

	this.redraw = function() {
		var $counterDiv = $('#divTimeTrackingCounter');
		
		if($counterDiv.length == 0)
			return;
		
		var strTime = "";
		var iSecs = this.counter;
		var iMins = Math.floor(iSecs/60);
		iSecs -= iMins * 60;
		
		if(iMins > 0) strTime = strTime + iMins + "m ";
		if(iSecs > 0) strTime = strTime + iSecs + "s ";
		
		$counterDiv.text(strTime);
	}

	this.show = function() {
		var $timerDiv = $('#divTimeTrackingBox').show();

		if($timerDiv.length == 0)
			return;
		
		var $playBtn = $('#btnTimeTrackingPlay');
		var $pauseBtn = $('#btnTimeTrackingPause');
		var $stopBtn = $('#btnTimeTrackingStop').show();
		
		if(this.enabled) {
			$playBtn.hide();
			$pauseBtn.show();
			
		} else {
			$playBtn.show();
			$pauseBtn.hide();
		}
		
		this.redraw();
		
		var _self = this;
		setTimeout(function(ms) {
			_self.increment();
		} ,10);
	}
	
	this.play = function(context, context_id) {
		// don't start twice
		if(this.enabled)
			return;

		if(null == context) context = '';
		if(null == context_id) context_id = '';

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'startTimer');
		formData.set('context', context);
		formData.set('context_id', context_id);

		var scope = this;

		genericAjaxPost(formData, '', '', function() {
			scope.enabled = true;
			scope.show();
		});
	}
	
	this.pause = function() {
		this.enabled = false;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'pauseTimerJson');
		genericAjaxPost(formData, '', '');

		var $timerDiv = $('#divTimeTrackingBox').show();
		
		if($timerDiv.length == 0)
			return;
		
		var $playBtn = $('#btnTimeTrackingPlay').show();
		var $pauseBtn = $('#btnTimeTrackingPause').hide();
		var $stopBtn = $('#btnTimeTrackingStop').show();
	}
	
	this.stop = function() {
		this.enabled = false;

		var $timerDiv = $('#divTimeTrackingBox').show();
		
		if($timerDiv.length == 0)
			return;
		
		var $playBtn = $('#btnTimeTrackingPlay').hide();
		var $pauseBtn = $('#btnTimeTrackingPause').hide();
		var $stopBtn = $('#btnTimeTrackingStop').hide();

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'pauseTimerJson');

		genericAjaxPost(formData, '', '', function(json) {
			if(json.status) {
				var $popup = genericAjaxPopup('peek','c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_TIMETRACKING}&context_id=0&mins=' + json.total_mins,null,false,'50%');
				$popup.one('dialogclose', function() {
					$playBtn.show();
					$stopBtn.show();
				});
			}
		} );
	}
	
	this.finish = function() {
		var $timerDiv = $('#divTimeTrackingBox').hide();
		
		if(0 === $timerDiv.length)
			return;
		
		this.counter = 0;

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'time_tracking');
		formData.set('action', 'clearEntry');
		genericAjaxPost(formData, '', '');
	}
};

var timeTrackingTimer = new timeTrackingTimerClass();

{if isset($session.timetracking_started) && $current_timestamp} {* timer is running *}
	{* Recover the total from any pause/unpause segments *}
	timeTrackingTimer.counter = {if isset($session.timetracking_total)}{$session.timetracking_total}{else}0{/if};
	{* Append the current runtime *}
	timeTrackingTimer.counter += {math equation="(x-y)" x=$current_timestamp y=$session.timetracking_started};
	timeTrackingTimer.enabled = true;
{elseif isset($session.timetracking_total)} {* timer is paused *}
	timeTrackingTimer.counter = {$session.timetracking_total};
{else}
	timeTrackingTimer.counter = 0;
{/if}

{if isset($session.timetracking_total) || isset($session.timetracking_started)}
	timeTrackingTimer.show();
{/if} 
</script>
{* end time tracking *}
