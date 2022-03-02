{if !empty($properties_links)}
{$uniqid = uniqid()}
<div id="{$uniqid}" class="cerb-links-container">
{$link_ctxs = Extension_DevblocksContext::getAll(false)}
	
{if !isset($links_label)}{$links_label = null}{/if}
{if !isset($links_label_compact)}{$links_label_compact = null}{/if}
{if !isset($peek)}{$peek = null}{/if}

{* Loop through the link contexts *}
{foreach from=$properties_links key=from_ctx_extid item=from_ctx_ids}
	{$from_ctx = $link_ctxs.$from_ctx_extid}

	{* Loop through the parent records for each link context *}
	{foreach from=$from_ctx_ids key=from_ctx_id item=link_counts}

		{* Do we have links to display? Always display the links block for this record *}
		{if !$links_label_compact}
		<fieldset data-cerb-links-container class="{if $peek}peek{else}properties{/if}" style="border:0;padding:0;background:none;{if !$peek}display:inline-block;vertical-align:top;{/if}" data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
			<legend>
				<a href="javascript:;" data-cerb-links-add data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">{if $links_label}{$links_label}{else}{if $page_context == $from_ctx_extid && $page_context_id == $from_ctx_id}{else}{$from_ctx->name} {/if}{'common.links'|devblocks_translate|capitalize}{/if}</a>
				&#x25be;
			</legend>
		{else}
			<div data-cerb-links-container data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
		{/if}
			
			<ul class="menu cerb-float" style="width:600px;column-count:3;column-gap:10px;display:none;">
				{foreach from=$link_ctxs item=link_ctx}
					{if $link_ctx->hasOption('links')}
						<li data-context="{$link_ctx->id}"><b>{$link_ctx->name}</b></li>
					{/if}
				{/foreach}
			</ul>
			
			<div class="cerb-buttonbar" style="display:inline;">
				{* Loop through each possible context so they remain alphabetized *}
				{$has_links = false}
				{foreach from=$link_ctxs item=link_ctx key=link_ctx_extid name=links}
				{if array_key_exists($link_ctx_extid, $link_counts)}
					{$link_ctx_aliases = Extension_DevblocksContext::getAliasesForContext($link_ctx)}
					{$link_ctx_alias = $link_ctx_aliases.plural|default:$link_ctx->name}
					<button type="button" data-context="{$link_ctx_extid}"><div class="badge-count">{$link_counts.$link_ctx_extid|number_format}</div> {$link_ctx_alias|capitalize}</button>
					{$has_links = true}
				{/if}
				{/foreach}
				{if !$has_links && !$links_label_compact}
					<div style="color:rgb(175,175,175);">({'common.none'|devblocks_translate|lower})</div>
				{/if}
			</div>

			{if $links_label_compact}
			<button type="button" data-cerb-links-add data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
				<span class="glyphicons glyphicons-circle-plus"></span>
				{'common.links'|devblocks_translate|capitalize}
			</button>
			{/if}
		
		{if !$links_label_compact}
			</fieldset>
		{else}
			</div>
		{/if}
	{/foreach}
{/foreach}
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	
	$div.find('[data-cerb-links-container]').on('cerb-redraw', function(e) {
		var $fieldset = $(this);
		var context = $fieldset.attr('data-context');
		var context_id = $fieldset.attr('data-context-id');

		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'records');
		formData.set('action', 'getLinkCountsJson');
		formData.set('context', context);
		formData.set('context_id', context_id);
		
		genericAjaxPost(formData, null, '', function(json) {
			var $buttonbar = $fieldset.find('div.cerb-buttonbar');
			$buttonbar.find('> *').remove();
			
			if($.isArray(json)) {
				for(var idx in json) {
					var row = json[idx];
					var $btn = $('<button type="button"/>')
						.css('margin-right', '5px')
						.attr('data-context', row.context)
						.text(' ' + row.label)
						;
					$('<div class="badge-count"/>').text(row.count).prependTo($btn);
					$buttonbar.append($btn);
				}

				{if !$links_label_compact}
				if(0 === json.length) {
					var $none = $('<div style="color:rgb(175,175,175);"/>').text('({'common.none'|devblocks_translate|lower|escape:'javascript'})');
					$buttonbar.append($none);
				}
				{/if}
			}
		});
	});
	
	$div.find('[data-cerb-links-container] div.cerb-buttonbar').click(function(e) {
		var $target = $(e.target);

		if($target.is('.badge-count'))
			$target = $target.closest('button');
		
		if(!$target.is('button'))
			return;

		var $fieldset = $target.closest('[data-cerb-links-container]');
		var context = $target.attr('data-context');
		var from_context = $fieldset.attr('data-context');
		var from_context_id = $fieldset.attr('data-context-id');
		
		var popup_id = 'links_' + context.replace(/\./g, '_');

		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'records');
		formData.set('action', 'linksOpen');
		formData.set('context', from_context);
		formData.set('context_id', from_context_id);
		formData.set('to_context', context);

		var $popup = genericAjaxPopup(popup_id,formData,null,false,'90%');
		
		$popup.on('links_save', function(e) {
			$div.find('[data-cerb-links-container]').trigger('cerb-redraw');
			
			var evt = jQuery.Event('cerb-links-changed');
			evt.context = from_context;
			evt.context_id = from_context_id;
			$div.trigger(evt);
		});
	});
	
	$div.find('[data-cerb-links-container] [data-cerb-links-add]').each(function() {
		var $a = $(this);
		
		var $menu = $a.closest('[data-cerb-links-container]')
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
			
			var $fieldset = $target.closest('[data-cerb-links-container]');
			var from_context = $fieldset.attr('data-context');
			var from_context_id = $fieldset.attr('data-context-id');
			
			var context = $target.attr('data-context');
			
			var $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context) + '&link_context=' + from_context + '&link_context_id=' + from_context_id,null,false,'90%');
			$popup.one('chooser_save', function(event) {
				event.stopPropagation();

				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'contextAddLinksJson');
				formData.set('from_context', from_context);
				formData.set('from_context_id', from_context_id);
				formData.set('context', context);

				for(var idx in event.values) {
					if(event.values.hasOwnProperty(idx)) {
						formData.append('context_id[]', event.values[idx]);
					}
				}
				
				genericAjaxPost(formData, null, null, function() {
					$div.find('[data-cerb-links-container]').trigger('cerb-redraw');
					
					var evt = jQuery.Event('cerb-links-changed');
					evt.context = from_context;
					evt.context_id = from_context_id;
					$div.trigger(evt);
				});
			});
		});
		
		$a.on('click', function(e) {
			e.stopPropagation();
			
			if($menu.is(':hidden')) {
				$menu
					.show()
					.position({ my:'left top', at:'left bottom', of:$div, collision: 'fit' })
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
