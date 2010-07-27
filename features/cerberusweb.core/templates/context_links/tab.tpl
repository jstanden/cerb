<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:10px;">
	<select name="to_context" onchange="chooserOpen(this);">
		<option value="">-- add links --</option>
		<option value="cerberusweb.contexts.address">Address</option>
		<option value="cerberusweb.contexts.opportunity">Opportunity</option>
		<option value="cerberusweb.contexts.org">Organization</option>
		<option value="cerberusweb.contexts.task">Task</option>
		<option value="cerberusweb.contexts.ticket">Ticket</option>
		<option value="cerberusweb.contexts.timetracking">Time Tracking</option>
		<option value="cerberusweb.contexts.worker">Worker</option>
	</select>
</form>

{if is_array($views)}
{foreach from=$views item=view}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/foreach}
{/if}

<script type="text/javascript">
function chooserOpen(ref) {
	$select = $(ref);
	$form = $select.closest('form');
	
	if(0==$select.val().length)
		return;
		
	$context = $select.val();
	
	$popup = genericAjaxPopup('chooser','c=internal&a=chooserOpen&context='+encodeURIComponent($context),null,true,'750');
	$popup.one('chooser_save', function(event) {
		event.stopPropagation();
		$id = $context.replace(/\./g,'_');
		$view = $('#view' + encodeURIComponent($id));
		
		$data = [ 
			'c=internal',
			'a=contextAddLinks',
			'from_context={$context|escape}',
			'from_context_id={$context_id|escape}', 
			'context='+$context
		];
		
		for(idx in event.values)
			$data.push('context_id[]='+encodeURIComponent(event.values[idx]));
		
		// [TODO] Switch to genericAjaxPost(), polymorph 'data/form'
		options = { };
		options.async = false;	
		options.type = 'POST';
		options.data = $data.join('&');
		options.url = DevblocksAppPath+'ajax.php',
		options.cache = false;
		$.ajax(options);

		if(0==$view.length) {
			$tabs = $form.closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			} else {
				//$form.submit();
			}
		} else {
			genericAjaxGet($view.attr('id'), 'c=internal&a=viewRefresh&id=' + $id);
		}
	} );
	
	$select.val('');
}

function removeSelectedContextLinks(view_id) {
	$view = $('#view' + view_id);
	context = view_id.replace('view','').replace(/\_/g,'.');
	
	$data = [ 
		'c=internal',
		'a=contextDeleteLinks',
		'from_context={$context|escape}',
		'from_context_id={$context_id|escape}', 
		'context='+context
	];
	$checks = $view.find('input:checkbox:checked').each(function() {
		$id = $(this).val();
		if(null != $id && $id > 0)
			$data.push('context_id[]='+$id);
	});
	
	// [TODO] Switch to genericAjaxPost(), polymorph 'data/form'
	options = { };
	options.async = false;	
	options.type = 'POST';
	options.data = $data.join('&');
	options.url = DevblocksAppPath+'ajax.php',
	options.cache = false;
	$.ajax(options);
	
	genericAjaxGet($view.attr('id'), 'c=internal&a=viewRefresh&id=' + view_id);
}
</script>
