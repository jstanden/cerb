{if empty($worklists)}
<form action="#" onsubmit="return false;">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's put this workspace to good use</h1>
	
	<p>
		You now have a blank worklists tab.  You can click the  
		<button type="button" onclick="$btn=$('#frmWorkspacePage{$page->id} button.config-page.split-left'); $(this).effect('transfer', { to:$btn, className:'effects-transfer' }, 500, function() { $btn.effect('pulsate', {  times: 3 }, function(e) { $(this).click(); } ); } );"><span class="glyphicons glyphicons-cogwheel"></span></button> 
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

<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/async-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

<script type="text/javascript">
$(function() {
	// Page title
	
	document.title = "{$tab->name|escape:'javascript' nofilter} - {$page->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	// Worklist loader
	
	var async_tasks = [];
	
	var cerbLoadWorklist = function(worklist_id, callback) {
		var $div = $('#worklistPlaceholder' + worklist_id);
	
		$div.fadeTo("fast", 0.2);
		
		genericAjaxGet('', 'c=pages&a=initWorkspaceList&list_id=' + worklist_id, function(html) {
			var $div = $('#worklistPlaceholder' + worklist_id);
			
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
			
			callback(null);
		});
	}
	
	{foreach from=$worklists item=worklist key=worklist_id}
	async_tasks.push(async.apply(cerbLoadWorklist, '{$worklist_id}'));
	{/foreach}

	async.series(async_tasks, function(err, data) {
		// Done!
	});
});
</script>