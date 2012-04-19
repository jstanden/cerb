{* begin time tracking *}
<div id="divTimeTrackingBox" style="display:{if isset($session.timetracking)}block{else}none{/if};position:fixed;width:300px;height:30px;top:2px;right:320px;background-color:rgb(50,50,50);color:rgb(255,255,255);opacity:.9;filter:alpha(opacity=90);z-index:2;vertical-align:middle;text-align:center;padding:5px;">
	{'timetracking.activity.tab'|devblocks_translate}: 
	<span style="font-size:18pt;font-weight:bold;"><span id="divTimeTrackingCounter">--</span></span>
	<button id="btnTimeTrackingPlay" type="button" onclick="timeTrackingTimer.play();" style="display:none;"><span class="cerb-sprite sprite-media_play_green"></span></button>
	<button id="btnTimeTrackingPause" type="button" onclick="timeTrackingTimer.pause();" style="display:none;"><span class="cerb-sprite sprite-media_pause"></span></button>
	<button id="btnTimeTrackingStop" type="button" onclick="timeTrackingTimer.stop();" style="display:none;"><span class="cerb-sprite sprite-media_stop_red"></span></button>
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
			var counterDiv = document.getElementById('divTimeTrackingCounter');
			if(null == counterDiv) return;
			
			var strTime = "";
			var iSecs = this.counter;
			var iMins = Math.floor(iSecs/60);
			iSecs -= iMins * 60;
			
			if(iMins > 0) strTime = strTime + iMins + "m ";
			if(iSecs > 0) strTime = strTime + iSecs + "s ";
			
			counterDiv.innerHTML = strTime;
		}

		this.show = function() {
			var timerDiv = document.getElementById('divTimeTrackingBox');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';
			
			btn = document.getElementById('btnTimeTrackingPlay');
			if(null != btn) btn.style.display = (this.enabled) ? 'none' : 'inline';
			btn = document.getElementById('btnTimeTrackingPause');
			if(null != btn) btn.style.display = (this.enabled) ? 'inline' : 'none';
			btn = document.getElementById('btnTimeTrackingStop');
			if(null != btn) btn.style.display = 'inline';
			
			this.redraw();
			
			var _self = this;
			setTimeout(function(ms) {
				_self.increment();
			} ,10);
		}
		
		this.play = function(context, context_id) {
			if(this.enabled) return; // don't start twice
			if(null == context) context = '';
			if(null == context_id) context_id = '';
			genericAjaxGet('','c=timetracking&a=startTimer&context=' + context + '&context_id=' + context_id);
			this.enabled = true;
	
			this.show();
		}
		
		this.pause = function() {
			this.enabled = false;
			genericAjaxGet('','c=timetracking&a=pauseTimerJson');
	
			var timerDiv = document.getElementById('divTimeTrackingBox');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';
			
			btn = document.getElementById('btnTimeTrackingPlay');
			if(null != btn) btn.style.display = 'inline';
			btn = document.getElementById('btnTimeTrackingPause');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnTimeTrackingStop');
			if(null != btn) btn.style.display = 'inline';
		}
		
		this.stop = function() {
			this.enabled = false;

			var timerDiv = document.getElementById('divTimeTrackingBox');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';

			btn = document.getElementById('btnTimeTrackingPlay');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnTimeTrackingPause');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnTimeTrackingStop');
			if(null != btn) btn.style.display = 'none';

			genericAjaxGet('','c=timetracking&a=pauseTimerJson', function(json) {
				if(json.status) {
					genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_TIMETRACKING}&context_id=0&mins=' + json.total_mins,null,false,'500');
				}
			} );
		}
		
		this.finish = function() {
			var timerDiv = document.getElementById('divTimeTrackingBox');
			if(null == timerDiv) return;
			timerDiv.style.display = 'none';
		
			this.counter = 0;
			genericAjaxGet('','c=timetracking&a=clearEntry');
			genericAjaxPopupClose('peek');
		}
	};
	
	timeTrackingTimer = new timeTrackingTimerClass();
	
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
