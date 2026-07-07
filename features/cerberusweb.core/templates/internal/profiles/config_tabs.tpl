{$uniqid = uniqid()}
{$tabset_id = "profile-tabs-{DevblocksPlatform::strAlphaNum($context,'','_')}"}
<form id="profileTabsConfig{$uniqid}" action="{devblocks_url}{/devblocks_url}" method="POST">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="configTabsSaveJson">
	<input type="hidden" name="context" value="{$context}">

	<div class="cerb-ui-toolbar-strip cerb-u-mb-3">
		<button type="button" class="cerb-add-tab-trigger cerb-ui-toolbar-button" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="0" data-edit="context:{$context}"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--title-sm">Display these tabs on this record type:</div>
		</div>

		<div class="cerb-sortable">
			{foreach from=$profile_tabs item=profile_tab name=tabs}
				<div class="cerb-sort-item cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-1">
					<span class="cerb-icons cerb-icon-menu-hamburger cerb-u-cursor-move cerb-u-fgg-5"></span>
					<a class="cerb-peek-trigger cerb-ui-pill" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="{$profile_tab->id}" data-edit="true">{$profile_tab->name}</a>
					<input type="hidden" name="profile_tabs[]" value="{$profile_tab->id}">
					<span class="cerb-u-text-muted">{$profile_tab->getExtension()->manifest->name}</span>
					<span class="cerb-u-text-muted cerb-u-truncate cerb-u-flex-1">{$profile_tab->options_kata|truncate:128}</span>
				</div>
			{/foreach}
		</div>
	</div>

	<div>
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#profileTabsConfig{$uniqid}');
	let $sortable = $frm.find('.cerb-sortable');

	Devblocks.formDisableSubmit($frm);

	// Peeks

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			let $item = $(this).closest('.cerb-sort-item');
			$item.find('a.cerb-peek-trigger').text(e.label);
		})
		.on('cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$(this).closest('.cerb-sort-item').remove();
			// The strip reflects the removal on Save → reload
		})
		;

	$frm.find('.cerb-add-tab-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			let $sort_item = $('<div/>')
				.addClass('cerb-sort-item cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-1')
				;

			$('<span class="cerb-icons cerb-icon-menu-hamburger cerb-u-cursor-move cerb-u-fgg-5"></span>')
				.appendTo($sort_item)
				;

			$('<a/>')
				.addClass('cerb-peek-trigger cerb-ui-pill')
				.attr('data-context', '{CerberusContexts::CONTEXT_PROFILE_TAB}')
				.attr('data-context-id', e.id)
				.attr('data-edit', 'true')
				.text(e.label)
				.appendTo($sort_item)
				;

			$('<input/>')
				.attr('type', 'hidden')
				.attr('name', 'profile_tabs[]')
				.val(e.id)
				.appendTo($sort_item)
				;

			// Type + options preview populate on Save → reload
			$('<span class="cerb-u-text-muted"></span>').appendTo($sort_item);
			$('<span class="cerb-u-text-muted cerb-u-truncate cerb-u-flex-1"></span>').appendTo($sort_item);

			$sortable.append($sort_item);
			if(window.CerbUI && CerbUI.Sortable) CerbUI.Sortable.from($sortable.get(0))?.refresh();

			// Add the new tab to the strip optimistically (Save → reload rebuilds it authoritatively)
			let $tabs = $('#{$tabset_id}');
			let $this = $(this);

			let $new_tab = $('<li/>');
			let tabHref = 'c=profiles&a=renderTab&tab_id=' + e.id + '&context={$context}&context_id={$context_id}';
			$new_tab.append($('<a/>').attr('href', tabHref).attr('draggable','false').text(e.label));

			// Insert before the trailing config-gear tab, then let CerbUI.Tabs pick it up
			$new_tab.insertBefore($tabs.children('li:last'));
			window.CerbUI?.Tabs?.from($tabs[0])?.sync();

			if(window.CerbUI && CerbUI.effects)
				CerbUI.effects.transfer($this[0], $new_tab[0], {});
		})
		;

	// Sortable

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($sortable.get(0), {
			helper: 'clone',
			handle: '.cerb-icon-menu-hamburger',
			items: '.cerb-sort-item'
		});

	// Submit

	$frm.find('button.save').on('click', function(e) {
		genericAjaxPost($frm, '', null, function() {
			e.stopPropagation();
			document.location.reload();
		});
	});
});
</script>