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
				<a data-cerb-links-add data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">{if $links_label}{$links_label}{else}{if $page_context == $from_ctx_extid && $page_context_id == $from_ctx_id}{else}{$from_ctx->name} {/if}{'common.links'|devblocks_translate|capitalize}{/if}</a>
				&#x25be;
			</legend>
		{else}
			<div data-cerb-links-container data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
		{/if}
			
			<ul data-cerb-links-menu hidden>
				{foreach from=$link_ctxs item=link_ctx}
					{if $link_ctx->hasOption('links')}
						<li data-context="{$link_ctx->id}" data-icon="{$link_ctx->params.icon|default:'collection'}">{$link_ctx->name}</li>
					{/if}
				{/foreach}
			</ul>
			
			<div class="cerb-buttonbar" style="display:inline;">
				{* Loop through each possible context so they remain alphabetized *}
				{$has_links = false}
				<ul class="cerb-ui-toolbar" data-cerb-links-buttonbar hidden>
					{foreach from=$link_ctxs item=link_ctx key=link_ctx_extid name=links}
					{if array_key_exists($link_ctx_extid, $link_counts)}
						{$link_ctx_aliases = Extension_DevblocksContext::getAliasesForContext($link_ctx)}
						{$link_ctx_alias = $link_ctx_aliases.plural|default:$link_ctx->name}
						<li data-context="{$link_ctx_extid}" data-icon="{$link_ctx->params.icon|default:'collection'}" data-badge="{$link_counts.$link_ctx_extid|number_format}">{$link_ctx_alias|capitalize}</li>
						{$has_links = true}
					{/if}
					{/foreach}
				</ul>
				{if !$has_links && !$links_label_compact}
					<div style="color:rgb(175,175,175);">({'common.none'|devblocks_translate|lower})</div>
				{/if}
			</div>

			{if $links_label_compact}
			<button type="button" data-cerb-links-add data-context="{$from_ctx_extid}" data-context-id="{$from_ctx_id}">
				<span class="cerb-icons cerb-icon-circle-plus"></span>
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');

	// Each link-type "buttonbar" is a CerbUI.Toolbar; counts render as a floating corner badge
	// (badgeStyle:'pill') to match the record-fields search buttons. The visible strip buttons fire onSelect,
	// which clicks the hidden source <li> — the delegated handler further down (bound on $div so it outlives a
	// cerb-redraw rebuild) reads its data-context and opens the links popup. Reconstruction is idempotent, so
	// rebuilding the toolbar on redraw is safe.
	const linksToolbarOpts = {
		badgeStyle: 'pill',
		onSelect: function(item, sourceLi) {
			if(sourceLi)
				$(sourceLi).trigger('click');
		}
	};

	const buildLinksToolbar = function(ul) {
		if(ul && ul.children.length && window.CerbUI && CerbUI.Toolbar)
			new CerbUI.Toolbar(ul, linksToolbarOpts);
	};

	$div.find('ul[data-cerb-links-buttonbar]').each(function() { buildLinksToolbar(this); });

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
			$buttonbar.empty();

			if($.isArray(json)) {
				const $ul = $('<ul class="cerb-ui-toolbar" data-cerb-links-buttonbar hidden/>');
				for(var idx in json) {
					var row = json[idx];
					$('<li/>')
						.attr('data-context', row.context)
						.attr('data-icon', row.icon)
						.attr('data-badge', row.count)
						.text(row.label)
						.appendTo($ul);
				}
				$buttonbar.append($ul);
				buildLinksToolbar($ul[0]);

				{if !$links_label_compact}
				if(0 === json.length) {
					var $none = $('<div style="color:rgb(175,175,175);"/>').text('({'common.none'|devblocks_translate|lower|escape:'javascript'})');
					$buttonbar.append($none);
				}
				{/if}
			}
		});
	});
	
	$div.on('click', 'ul[data-cerb-links-buttonbar] > li', function(e) {
		var $target = $(this);

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
	
	// Opens the record chooser to attach links of a given type, then refreshes counts on save.
	const openLinkChooser = function(context, from_context, from_context_id) {
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
	};

	// The "add links" dropdown is a CerbUI.Menu (filterable list of linkable contexts) anchored to the
	// legend/`+` trigger; onSelect opens the same chooser popup. Source <ul> is static, so build once.
	$div.find('[data-cerb-links-container]').each(function() {
		const $container = $(this);
		const trigger = $container.find('[data-cerb-links-add]')[0];
		const menuUl = $container.find('ul[data-cerb-links-menu]')[0];

		if(!trigger || !menuUl || !(window.CerbUI && CerbUI.Menu))
			return;

		const from_context = $container.attr('data-context');
		const from_context_id = $container.attr('data-context-id');

		const menu = new CerbUI.Menu(menuUl, {
			filter: true,
			onRenderItem: function(renderedLi, sourceLi) {
				const icon = sourceLi.dataset.icon;
				if(icon) {
					const ico = document.createElement('span');
					ico.className = 'cerb-icons cerb-icon-' + icon;
					ico.setAttribute('aria-hidden', 'true');
					ico.style.marginRight = '0.5em';
					renderedLi.insertBefore(ico, renderedLi.firstChild);
				}
			},
			onSelect: function(renderedLi, sourceLi) {
				openLinkChooser(sourceLi.getAttribute('data-context'), from_context, from_context_id);
			}
		});

		$(trigger).on('click', function(e) {
			e.stopPropagation();
			menu.isOpen() ? menu.close() : menu.open(trigger);
		});
	});
});
</script>
{/if}
