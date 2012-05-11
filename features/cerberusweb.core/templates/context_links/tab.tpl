{$link_contexts = Extension_DevblocksContext::getAll(false)}

{if $context != CerberusContexts::CONTEXT_WORKER}
<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:10px;">
	<select onchange="chooserOpen(this);">
		<option value="">-- find &amp; link --</option>
		{foreach from=$link_contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['options'][0]['find'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
	</select>
	
	<select onchange="linkAddContext(this);">
		<option value="">-- create &amp; link --</option>
		{foreach from=$link_contexts item=context_mft key=context_mft_id}
		{if isset($context_mft->params['options'][0]['create'])}
		<option value="{$context_mft_id}">{$context_mft->name}</option>
		{/if}
		{/foreach}
	</select>
</form>
{/if}

<div id="divConnections"></div>

<script type="text/javascript">
{if $context != CerberusContexts::CONTEXT_WORKER}
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
	
	$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context=' + $context + '&context_id=0&link_context={$context}&link_context_id={$context_id}',null,false,'500');
	$popup.one('dialogclose', reload_action);
	
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

$forms = $('#divConnections').delegate('DIV[id^=view]','view_refresh',function() {
	id = $(this).attr('id').replace('view','');
	$(this)
		.find('TABLE[id$=_actions] > TBODY > TR:first > TD:first')
		.prepend($('<button type="button" onclick="removeSelectedContextLinks(\''+id+'\')">Unlink</button>'))
		;
});

{else}{* Is worker profile *}

{/if}
</script>

<script type="text/javascript">
	$connections = $('#divConnections');
	$ajaxQueue = $({});

	{foreach from=$contexts item=to_context}
	$ajaxQueue.queue(function(next) {
		$div = $('<div></div>');
		$div
			.appendTo($connections)
			.html($('<div class="lazy" style="font-size:18pt;text-align:center;padding:50px;margin:20px;background-color:rgb(232,242,255);">Loading...</div>'))
			;
		
		window_fold = $(window).height() + $(window).scrollTop();
		div_top = $div.offset().top;

		if(div_top > window_fold + 100) {
			$div.one('appear',function(event) {
				var $this = $(this);
				$ajaxQueue.queue(function(next) {	
					genericAjaxGet(
						$this,
						'c=internal&a=initConnectionsView&context={$context}&context_id={$context_id}&to_context={$to_context}',
						function(html) {
							$this
								.html(html)
								;
							//$this.find('DIV[id^=view]:first').trigger('view_refresh');
							next();
						}
					);
				});
			});
			next();
			
		} else {
			genericAjaxGet(
				$div,
				'c=internal&a=initConnectionsView&context={$context}&context_id={$context_id}&to_context={$to_context}',
				function(html){
					$div
						.html(html)
						;
					//$div.find('DIV[id^=view]:first').trigger('view_refresh');
					next();
				}
			);
		}
	});
	{/foreach}

	$(window).scroll(function(event) {
		window_fold = $(window).height() + $(window).scrollTop();
		
		$lazies = $connections.find('DIV.lazy');

		// If we have nothing else to load, unbind
		if(0 == $lazies.length) {
			$(window).unbind(event);
			return;
		}
		
		$lazies.each(function() {
			div_top = $(this).offset().top;
			if(div_top < window_fold + 50) {
				$(this)
					.removeClass('lazy')
					.parent()
					.trigger('appear')
					;
			}
		});
	});
</script>
