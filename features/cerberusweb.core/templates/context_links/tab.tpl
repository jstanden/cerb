{$contexts = DevblocksPlatform::getExtensions('devblocks.context', false)}
{$null = asort($contexts)}

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:10px;">
	<select onchange="chooserOpen(this);">
		<option value="">-- find &amp; link --</option>
		{foreach from=$contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['link_options'][0]['find'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
	</select>
	
	<select onchange="linkAddContext(this);">
		<option value="">-- create &amp; link --</option>
		{foreach from=$contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['link_options'][0]['create'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
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
function linkAddContext(ref) {
	$select = $(ref);
	$form = $select.closest('form');

	if(0==$select.val().length)
		return;

	$context = $select.val();

	reload_action = function(event) {
		// Reload the tab
		event.stopPropagation();
		$id = $context.replace(/\./g,'_');
		$view = $('#view' + encodeURIComponent($id));
	
		if(0==$view.length) {
			$tabs = $form.closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		} else {
			genericAjaxGet($view.attr('id'), 'c=internal&a=viewRefresh&id=' + $id);
		}
	}
	
	// [TODO] Make this generic
	// [TODO] Move peek to context?

	if($context == 'cerberusweb.contexts.task') {
		$popup = genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id=0&context={$context}&context_id={$context_id}',null,false,'500');
		$popup.one('dialogclose', reload_action);
	} else if($context == 'cerberusweb.contexts.address') {
		$popup = genericAjaxPopup('peek','c=contacts&a=showAddressPeek&id=0&context={$context}&context_id={$context_id}',null,false,'500');
		$popup.one('dialogclose', reload_action);
	} else if($context == 'cerberusweb.contexts.org') {
		$popup = genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id=0&context={$context}&context_id={$context_id}',null,false,'500');
		$popup.one('dialogclose', reload_action);
	} else if($context == 'cerberusweb.contexts.opportunity') {
		$popup = genericAjaxPopup('peek','c=crm&a=showOppPanel&id=0&context={$context}&context_id={$context_id}',null,false,'500');
		$popup.one('dialogclose', reload_action);
	}
	
	$select.val('');
}
	
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
			'from_context={$context}',
			'from_context_id={$context_id}', 
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
	context = $view.find('FORM input:hidden[name=context_id]').val();
	
	$data = [ 
		'c=internal',
		'a=contextDeleteLinks',
		'from_context={$context}',
		'from_context_id={$context_id}', 
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
