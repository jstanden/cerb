{$link_contexts = Extension_DevblocksContext::getAll(false)}

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

<div id="divConnections"></div>

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
	
	$popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpen&context='+encodeURIComponent($context) + '&link_context={$context}&link_context_id={$context_id}',null,true,'750');
	$popup.one('chooser_save', function(event) {
		event.stopPropagation();
		$id = $context.replace(/\./g,'_');
		$view = $('#view' + encodeURIComponent($id));
		
		$data = [ 
			'c=internal',
			'a=contextAddLinksJson',
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
		options.success = function(json) {
			if(json.links_count) {
				$connections = $('#divConnections');
				$tabs = $connections.closest('div.ui-tabs');
				$tab = $tabs.find('> ul.ui-tabs-nav > li.ui-tabs-active');
				$tab.find('> a > div.tab-badge').html(json.links_count);
			}
		};
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

function removeSelectedContextLinks(ref) {
	view_id = $(ref).closest('form').find('input:hidden[name=view_id]').val();
	
	$view = $('#view' + view_id);
	context = $view.find('FORM input:hidden[name=context_id]').val();
	
	$data = [ 
		'c=internal',
		'a=contextDeleteLinksJson',
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
	options.success = function(json) {
		if(json.links_count) {
			$connections = $('#divConnections');
			$tabs = $connections.closest('div.ui-tabs');
			$tab = $tabs.find('> ul.ui-tabs-nav > li.ui-tabs-active');
			$tab.find('> a > div.tab-badge').html(json.links_count);
		}
	};
	$.ajax(options);
	
	genericAjaxGet($view.attr('id'), 'c=internal&a=viewRefresh&id=' + view_id);
}

$forms = $('#divConnections').delegate('DIV[id^=view]','view_refresh',function() {
	$actions = $(this).find('DIV[id$=_actions]');
	
	if(0 == $actions.find('button.unlink').length) {
		$actions.prepend($('<button type="button" class="unlink" style="display:none;" onclick="removeSelectedContextLinks(this);">Unlink</button>&nbsp;'));
	}
});
</script>

<script type="text/javascript">
	$connections = $('#divConnections');
	
	$ajaxQueue = $({});

	{foreach from=$contexts item=to_context}
	$ajaxQueue.queue(function(next) {
		$div = $('<div style="margin-bottom:10px;"></div>');
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
							
							$this
								.find('DIV[id$=_actions]')
								.prepend($('<button type="button" class="unlink" style="display:none;" onclick="removeSelectedContextLinks(this);">Unlink</button>'))
								;
							
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
					
					$div
						.find('DIV[id$=_actions]')
						.prepend($('<button type="button" class="unlink" style="display:none;" onclick="removeSelectedContextLinks(this);">Unlink</button>'))
						;
					
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
