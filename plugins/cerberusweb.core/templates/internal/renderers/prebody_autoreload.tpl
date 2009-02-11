<div id="divAutoRefresh" style="display:{if isset($session.autorefresh)}block{else}none{/if};position:fixed;width:150px;height:80px;top:20;right:20;background-color:rgb(50,50,50);color:rgb(255,255,255);opacity:.9;filter:alpha(opacity=90);z-index:2;vertical-align:middle;text-align:center;padding:5px;">
	Auto-Refresh<br>
	<div style="font-size:18pt;font-weight:bold;"><span id="divAutoRefreshCounter">--</span></div>
	<form action="" method="POST">
	<button id="btnAutoRefreshPlay" type="button" onclick="autoRefreshTimer.play();" style="display:none;"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_play_green.png{/devblocks_url}"></button>
	<button id="btnAutoRefreshPause" type="button" onclick="autoRefreshTimer.pause();" style="display:none;"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_pause.png{/devblocks_url}"></button>
	<button id="btnAutoRefreshStop" type="button" onclick="autoRefreshTimer.stop();" style="display:none;"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_stop_red.png{/devblocks_url}"></button>
	<button id="btnAutoRefreshGo" type="submit" onclick="" style="display:none;"></button>
	</form>
</div>
<script type="text/javascript">
	{literal}
	var autoRefreshTimerClass = function() {
		this.counter = 20;
		this.enabled = false;
		this.url = '';
		this.timer = null;
	
		this.decrement = function() {
			if(!this.enabled) return;
	
			--this.counter;
			
			if(0 == this.counter) {
				var btn = document.getElementById('btnAutoRefreshGo');
				btn.form.action = this.url;
				btn.click();
				return;
			}
			
			this.redraw();	
			
			if(this.enabled) {
				var _self = this;
				this.timer = setTimeout(function(ms){
					_self.decrement();
				},1000);
			}
		}
	
		this.redraw = function() {
			var counterDiv = document.getElementById('divAutoRefreshCounter');
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
			var timerDiv = document.getElementById('divAutoRefresh');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';
			
			btn = document.getElementById('btnAutoRefreshPlay');
			if(null != btn) btn.style.display = (this.enabled) ? 'none' : 'inline';
			btn = document.getElementById('btnAutoRefreshPause');
			if(null != btn) btn.style.display = (this.enabled) ? 'inline' : 'none';
			btn = document.getElementById('btnAutoRefreshStop');
			if(null != btn) btn.style.display = 'inline';
			
			this.redraw();
			
			var _self = this;
			this.timer = setTimeout(function(ms) {
				_self.decrement();
			},10);
		}
		
		this.start = function(url, secs) {
			if(this.enabled) return; // don't start twice
			this.counter = (null==secs || Number.NaN==secs) ? 300 : secs;
			this.url = (null == url) ? window.location.href : url;
			this.play();
	
			genericAjaxGet('','c=internal&a=startAutoRefresh&url=' + encodeURIComponent(url) + "&secs="+this.counter);
		}
		
		this.play = function() {
			if(this.enabled) return; // don't start twice
			this.enabled = true;
			this.show();
		}
		
		this.pause = function() {
			this.enabled = false;
	
			var timerDiv = document.getElementById('divAutoRefresh');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';
			
			clearTimeout(this.timer);
			
			btn = document.getElementById('btnAutoRefreshPlay');
			if(null != btn) btn.style.display = 'inline';
			btn = document.getElementById('btnAutoRefreshPause');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnAutoRefreshStop');
			if(null != btn) btn.style.display = 'inline';
		}
		
		this.stop = function() {
			this.enabled = false;

			var timerDiv = document.getElementById('divAutoRefresh');
			if(null == timerDiv) return;
			timerDiv.style.display = 'block';

			btn = document.getElementById('btnAutoRefreshPlay');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnAutoRefreshPause');
			if(null != btn) btn.style.display = 'none';
			btn = document.getElementById('btnAutoRefreshStop');
			if(null != btn) btn.style.display = 'none';

			this.finish();
		}
		
		this.finish = function() {
			var timerDiv = document.getElementById('divAutoRefresh');
			if(null == timerDiv) return;
			timerDiv.style.display = 'none';
		
			this.counter = 20;
			genericAjaxGet('','c=internal&a=stopAutoRefresh');
		}
	};
	
	autoRefreshTimer = new autoRefreshTimerClass();
	{/literal}
	
	{if isset($session.autorefresh)}
		if("{$session.autorefresh.url}" != window.location.href)
			autoRefreshTimer.finish();
		else
			autoRefreshTimer.start('{$session.autorefresh.url}',{$session.autorefresh.secs|string_format:"%d"});
	{/if} 
</script>