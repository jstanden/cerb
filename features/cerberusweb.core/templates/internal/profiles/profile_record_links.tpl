{if !empty($properties_links)}
{$uniqid = uniqid()}
<div id="{$uniqid}" class="cerb-links-container">
{$link_ctxs = Extension_DevblocksContext::getAll(false)}

{* Loop through the link contexts *}
{foreach from=$properties_links key=from_ctx_extid item=from_ctx_ids}
	{$from_ctx = $link_ctxs.$from_ctx_extid}

	{* Loop through the parent records for each link context *}
	{foreach from=$from_ctx_ids key=from_ctx_id item=link_counts}

		{* Do we have links to display? Always display the links block for this record *}
		<fieldset class="{if $peek}peek{else}properties{/if}" style="border:0;padding:0;background:none;{if !$peek}display:inline-block;vertical-align:top;{/if}" data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
			<legend>
				<a href="javascript:;" data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">{if $links_label}{$links_label}{else}{if $page_context == $from_ctx_extid && $page_context_id == $from_ctx_id}{else}{$from_ctx->name} {/if}{'common.links'|devblocks_translate|capitalize}{/if}</a>
				&#x25be;
			</legend>
			
			<ul class="menu cerb-float" style="width:600px;column-count:3;column-gap:10px;display:none;">
				{foreach from=$link_ctxs item=link_ctx}
				{if $link_ctx->hasOption('links')}
				<li data-context="{$link_ctx->id}"><b>{$link_ctx->name}</b></li>
				{/if}
				{/foreach}
			</ul>
			
			<div class="cerb-buttonbar">
				{* Loop through each possible context so they remain alphabetized *}
				{$has_links = false}
				{foreach from=$link_ctxs item=link_ctx key=link_ctx_extid name=links}
				{if $link_counts.$link_ctx_extid}
					<button type="button" data-context="{$link_ctx_extid}"><div class="badge-count">{$link_counts.$link_ctx_extid|number_format}</div> {$link_ctx->name}</button>
					{$has_links = true}
				{/if}
				{/foreach}
				
				{if !$has_links}
					<div style="color:rgb(175,175,175);">({'common.none'|devblocks_translate|lower})</div>
				{/if}
			</div>
		</fieldset>
		
	{/foreach}
{/foreach}
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	
	$div.find('fieldset').on('cerb-redraw', function(e) {
		var $fieldset = $(this);
		var context = $fieldset.attr('data-context');
		var context_id = $fieldset.attr('data-context-id');

		var formData = new FormData();
		formData.append('c', 'internal');
		formData.append('a', 'getLinkCountsJson');
		formData.append('context', context);
		formData.append('context_id', context_id);
		
		genericAjaxPost(formData, null, '', function(json) {
			var $buttonbar = $fieldset.find('div.cerb-buttonbar');
			$buttonbar.find('> *').remove();
			
			if($.isArray(json)) {
				for(idx in json) {
					var row = json[idx];
					var $btn = $('<button type="button"/>')
						.css('margin-right', '5px')
						.attr('data-context', row.context)
						.text(' ' + row.label)
						;
					$('<div class="badge-count"/>').text(row.count).prependTo($btn);
					$buttonbar.append($btn);
				}
				
				if(json.length == 0) {
					var $none = $('<div style="color:rgb(175,175,175);"/>').text('({'common.none'|devblocks_translate|lower|escape:'javascript'})');
					$buttonbar.append($none);
				}
			}
		});
	});
	
	$div.find('fieldset div.cerb-buttonbar').click(function(e) {
		var $target = $(e.target);
		var $fieldset = $target.closest('fieldset');
		var context = $target.attr('data-context');
		var from_context = $fieldset.attr('data-context');
		var from_context_id = $fieldset.attr('data-context-id');

		if(!$target.is('button'))
			return;
		
		var popup_id = 'links_' + context.replace(/\./g, '_');

		var formData = new FormData();
		formData.append('c', 'internal');
		formData.append('a', 'linksOpen');
		formData.append('context', from_context);
		formData.append('context_id', from_context_id);
		formData.append('to_context', context);

		var $popup = genericAjaxPopup(popup_id,formData,null,false,'90%');
		
		$popup.on('links_save', function(e) {
			$div.find('fieldset').trigger('cerb-redraw');
			
			var evt = jQuery.Event('cerb-links-changed');
			evt.context = from_context;
			evt.context_id = from_context_id;
			$div.trigger(evt);
		});
	});
	
	$div.find('fieldset legend a').each(function() {
		var $a = $(this);
		
		var $menu = $a.closest('fieldset')
			.find('ul.menu')
			.hide()
			.menu()
			.hoverIntent({
				sensitivity:10,
				interval:50,
				timeout:0,
				over:function(e) {
				},
				out:function(e) {
					$(this).hide();
				}
			})
			;
		
		// Catch menu item clicks
		$menu.on('click', function(e, ui) {
			e.stopPropagation();
			$menu.hide();
			var $target = $(e.target);
			
			if($target.is('b'))
				$target = $target.closest('li');
			
			if(!$target.is('li'))
				return;
			
			var $fieldset = $target.closest('fieldset');
			var from_context = $fieldset.attr('data-context');
			var from_context_id = $fieldset.attr('data-context-id');
			
			var context = $target.attr('data-context');
			
			var $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpen&context=' + encodeURIComponent(context) + '&link_context=' + from_context + '&link_context_id=' + from_context_id,null,false,'90%');
			$popup.one('chooser_save', function(event) {
				event.stopPropagation();
				var $id = context.replace(/\./g,'_');
				var $view = $('#view' + encodeURIComponent($id));
				
				var $data = [ 
					'c=internal',
					'a=contextAddLinksJson',
					'from_context=' + from_context,
					'from_context_id=' + from_context_id,
					'context=' + context
				];
				
				for(idx in event.values)
					$data.push('context_id[]='+encodeURIComponent(event.values[idx]));
				
				var options = { };
				options.type = 'POST';
				options.async = false;
				options.data = $data.join('&');
				options.url = DevblocksAppPath+'ajax.php',
				options.cache = false;
				options.success = function(json) {
					$div.find('fieldset').trigger('cerb-redraw');
					
					var evt = jQuery.Event('cerb-links-changed');
					evt.context = from_context;
					evt.context_id = from_context_id;
					$div.trigger(evt);
				};
				
				if(null == options.headers)
					options.headers = {};

				options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
				
				$.ajax(options);
			});
		});
		
		$a.on('click', function(e) {
			e.stopPropagation();
			var $this = $(this);
			
			if($menu.is(':hidden')) {
				$menu
					.show()
					.position({ my:'left top', at:'left bottom', of:$this, collision: 'fit' })
					;
			} else {
				$menu
					.hide()
					;
			}
		});
	});
});
</script>
{/if}
