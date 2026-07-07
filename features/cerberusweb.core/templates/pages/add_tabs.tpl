{if empty($workspace_tabs) && CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Let's add some tabs to your page</div>
				<div class="cerb-ui-header--subtitle">
					<p>
						Once you've created a new workspace page you can add tabs to organize your content.
					</p>

					<p>
						Depending on the plugins you have installed, a tab can be one of several <b>types</b>.  The default is <i>Worklists</i>, which displays as many lists of specific information as you want.  The other tab types are specialized for specific purposes, such as informational dashboards and browsing the knowledgebase by category.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
{/if}

{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="pages">
	<input type="hidden" name="a" value="saveTabs">
	<input type="hidden" name="id" value="{$page->id}">

	<div class="cerb-ui-toolbar-strip cerb-u-mb-3">
		<button type="button" class="cerb-peek-trigger cerb-ui-toolbar-button" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="0" data-edit="page.id:{$page->id}"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--title-sm">Display these tabs on this workspace page:</div>
		</div>

		<div class="cerb-sortable">
			{foreach from=$workspace_tabs item=workspace_tab name=tabs}
				<div class="cerb-sort-item cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-py-1">
					<span class="cerb-icons cerb-icon-menu-hamburger cerb-u-cursor-move cerb-u-fgg-5"></span>
					<a class="cerb-peek-trigger cerb-ui-pill" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="{$workspace_tab->id}" data-edit="true">{$workspace_tab->name}</a>
					<input type="hidden" name="workspace_tabs[]" value="{$workspace_tab->id}">
					<span class="cerb-u-text-muted">{$workspace_tab->getExtension()->manifest->name}</span>
					<span class="cerb-u-text-muted cerb-u-truncate cerb-u-flex-1">{$workspace_tab->options_kata|truncate:128}</span>
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
	let $frm = $('#{$uniq_id}');
	let $sortable = $frm.find('.cerb-sortable');

	Devblocks.formDisableSubmit($frm);

	$frm.find('button.cerb-peek-trigger')
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
					.attr('data-context', '{CerberusContexts::CONTEXT_WORKSPACE_TAB}')
					.attr('data-context-id', e.id)
					.attr('data-edit', 'true')
					.text(e.label)
					.appendTo($sort_item)
				;

				$('<input/>')
					.attr('type', 'hidden')
					.attr('name', 'workspace_tabs[]')
					.val(e.id)
					.appendTo($sort_item)
				;

				// Type + options preview populate on Save → reload
				$('<span class="cerb-u-text-muted"></span>').appendTo($sort_item);
				$('<span class="cerb-u-text-muted cerb-u-truncate cerb-u-flex-1"></span>').appendTo($sort_item);

				$sortable.append($sort_item);
				if(window.CerbUI && CerbUI.Sortable) CerbUI.Sortable.from($sortable.get(0))?.refresh();

				// Add the new tab to the strip optimistically (Save → reload rebuilds it authoritatively)
				let $this = $(this);
				let $tabs = $('#pageTabs{$page->id}');

				let $new_tab = $('<li/>').attr('data-tab-id',e.id);
				$new_tab.append($('<a/>').attr('href', e.tab_url.split('ajax.php?').pop()).attr('draggable','false').append($('<span/>').text(e.label)));

				// Insert before the trailing add-tabs gear, then let CerbUI.Tabs pick it up
				$new_tab.insertBefore($tabs.children('li:last'));
				window.CerbUI?.Tabs?.from($tabs[0])?.sync();

				if(window.CerbUI && CerbUI.effects)
					CerbUI.effects.transfer($this[0], $new_tab[0], {});
			})
		;

	$frm.find('a.cerb-peek-trigger')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				e.stopPropagation();
				let $item = $(this).closest('.cerb-sort-item');
				$item.find('a.cerb-peek-trigger').text(e.label);
			})
			.on('cerb-peek-deleted', function(e) {
				e.stopPropagation();
				// [TODO] Also remove the pages tab?
				$(this).closest('.cerb-sort-item').remove();
				// The strip reflects the removal on Save → reload
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
		e.stopPropagation();
		genericAjaxPost($frm, '', null, function() {
			document.location.reload();
		});
	});
});
</script>
