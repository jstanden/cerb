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
			Loading: {$worklist->name}<br>
			{include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
		</div>
	</div>
{/foreach}
</div>

<script type="text/javascript">
$(function() {
	// Page title
	
	document.title = "{$tab->name|escape:'javascript' nofilter} - {$page->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	// Worklist loader
	
	var async_tasks = [];
	
	var cerbLoadWorklist = function(worklist_id, callback) {
		var $div = $('#worklistPlaceholder' + worklist_id);
	
		$div.fadeTo("fast", 0.2);

		var formData = new FormData();
		formData.set('c', 'pages');
		formData.set('a', 'renderWorklist');
		formData.set('list_id', worklist_id);

		genericAjaxPost(formData, '', '', function(html) {
			var $div = $('#worklistPlaceholder' + worklist_id);
			
			if(null != $div) {
				$div.fadeOut();
				
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