{if empty($workspace_tabs) && CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $page, $active_worker)}
<div class="help-box">
	<h1 style="margin-bottom:5px;text-align:left;">Let's add some tabs to your page</h1>
	
	<p>
		Once you've created a new workspace page you can add tabs to organize your content.
	</p>

	<p>
		Depending on the plugins you have installed, a tab can be one of several <b>types</b>.  The default is <i>Worklists</i>, which displays as many lists of specific information as you want.  The other tab types are specialized for specific purposes, such as informational dashboards and browsing the knowledgebase by category.
	</p>
</div>
{/if}

{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="pages">
	<input type="hidden" name="a" value="saveTabs">
	<input type="hidden" name="id" value="{$page->id}">

	<button style="margin-bottom:10px;" type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="0" data-edit="page.id:{$page->id}"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>

	<fieldset class="peek">
		<legend>Display these tabs on this workspace page:</legend>

		<table class="cerb-table cerb-sortable">
			{foreach from=$workspace_tabs item=workspace_tab name=tabs}
				<tbody class="cerb-sort-item">
					<tr>
						<td>
							<span class="cerb-icons cerb-icon-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:0.5em;"></span>
						</td>
						<td>
							<div style="display:inline-block;border:1px solid var(--cerb-color-background-contrast-200);border-radius:5px 5px 0 0;padding:5px 10px;">
								<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="{$workspace_tab->id}" data-edit="true"><b>{$workspace_tab->name}</b></a>
								<input type="hidden" name="workspace_tabs[]" value="{$workspace_tab->id}">
							</div>
						</td>
						<td>
							{$workspace_tab->getExtension()->manifest->name}
						</td>
						<td>
							{$workspace_tab->options_kata|truncate:128}
						</td>
					</tr>
				</tbody>
			{/foreach}
		</table>
	</fieldset>

	<div>
		<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
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

				let $sort_item = $('<tbody/>')
					.addClass('cerb-sort-item')
				;

				let $tr = $('<tr/>').appendTo($sort_item);

				let $td = $('<td/>').appendTo($tr);

				$('<span class="cerb-icons cerb-icon-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:0.5em;"></span>')
					.appendTo($td)
				;

				$td = $('<td/>').appendTo($tr);

				let $div = $('<div style="display:inline-block;border:1px solid var(--cerb-color-background-contrast-200);border-radius:5px 5px 0 0;padding:5px 10px;">')
					.appendTo($td)
				;

				$('<a/>')
					.addClass('cerb-peek-trigger no-underline')
					.attr('data-context', '{CerberusContexts::CONTEXT_WORKSPACE_TAB}')
					.attr('data-context-id', e.id)
					.attr('data-edit', 'true')
					.append($('<b/>').text(e.label))
					.appendTo($div)
				;

				$('<input/>')
					.attr('type', 'hidden')
					.attr('name', 'workspace_tabs[]')
					.val(e.id)
					.appendTo($td)
				;

				$('<td/>').appendTo($tr);
				$('<td/>').appendTo($tr);

				$sortable.append($sort_item);
				$sortable.sortable('refresh');

				// Add new tab
				let $this = $(this);
				let $tabs = $('#pageTabs{$page->id}');
				
				let $new_tab = $('<li/>').attr('data-tab-id',e.id);
				$new_tab.append($('<a/>').attr('href',e.tab_url).attr('draggable','false').append($('<span/>').text(e.label)));
				
				$new_tab.insertBefore($tabs.find('.ui-tabs-nav > li:last'));
				
				$tabs.tabs('refresh');
				
				$this.effect('transfer', { to:$new_tab, className:'effects-transfer' }, 500, function() { });
				
				$tabs.tabs('option', 'active', -1);
			})
		;

	$frm.find('a.cerb-peek-trigger')
			.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				e.stopPropagation();
				let $item = $(this).closest('tbody.cerb-sort-item');
				$item.find('a.cerb-peek-trigger > b').text(e.label);
			})
			.on('cerb-peek-deleted', function(e) {
				e.stopPropagation();
				// [TODO] Also remove the pages tab?
				$(this).closest('tbody.cerb-sort-item').remove();

				let $tabs = $('#pageTabs{$page->id}');
				$tabs.tabs('option', 'active', -1);
			})
	;

	// Sortable

	$sortable
		.sortable({
			tolerance: 'pointer',
			helper: 'clone',
			handle: '.cerb-icon-menu-hamburger',
			items: '.cerb-sort-item',
			opacity: 0.7
		})
	;

	// Submit

	$frm.find('button.submit').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPost($frm, '', null, function() {
			document.location.reload();
		});
	});
});
</script>