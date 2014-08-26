{if empty($worklists)}
<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's put this workspace to good use</h1>
	
	<p>
		You now have a blank worklists tab.  You can click the  
		<button type="button" onclick="$btn=$('#frmWorkspacePage{$page->id} button.config-page.split-left'); $(this).effect('transfer', { to:$btn, className:'effects-transfer' }, 500, function() { $btn.effect('pulsate', {  times: 3 }, function(e) { $(this).click(); } ); } );"><span class="cerb-sprite2 sprite-gear"></span></button> 
		button and select <b>Edit Tab</b> from the menu to display any number of worklists right here in a single place. 
	</p>
</div>
</form>
{/if}

<div id="divWorklistsTab{$tab->id}">
{foreach from=$worklists item=worklist key=worklist_id}
	<div id="worklistPlaceholder{$worklist_id}" style="margin-bottom:10px;">
		<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;">
			Loading: {$worklist->list_view->title}<br>
			<span class="cerb-ajax-spinner"></span>
		</div>
	</div>
{/foreach}
</div>

<script type="text/javascript">
// Page title

document.title = "{$tab->name|escape:'javascript'} - {$page->name|escape:'javascript'} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript'}";

// Worklist loader

$.worklistAjaxLoader = function() {
	this.worklist_ids = [];
	this.is_running = false;
};

$.worklistAjaxLoader.prototype = {
	add: function(worklist_id) {
		this.worklist_ids.push(worklist_id);
		this.next();
	},
	
	next: function() {
		if(this.worklist_ids.length == 0)
			return;
		
		if(this.is_running == true)
			return;
		
		var worklist_id = this.worklist_ids.shift();
		var loader = this;
		var $div = $('#worklistPlaceholder' + worklist_id);
		
		var cb = function(html) {
			if(null != $div) {
				$div.fadeOut();
				
				var $worklist = 
					$('<div style="margin-bottom:10px;"></div>')
					.fadeTo("fast", 0.2)
					.html(html)
					.insertAfter($div)
					.fadeTo("fast", 1.0)
					;
				
				$div.remove();
			}
			
			loader.is_running = false;
			loader.next();
		}

		$div.fadeTo("fast", 0.2);
		
		this.is_running = true;
		
		genericAjaxGet('', 'c=pages&a=initWorkspaceList&list_id=' + worklist_id, cb);
	},
};

var $worklistAjaxLoader = new $.worklistAjaxLoader();

{foreach from=$worklists item=worklist key=worklist_id}
$worklistAjaxLoader.add({$worklist_id});
{/foreach}
</script>