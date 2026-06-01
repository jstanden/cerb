{$uniqid = uniqid()}
{$tabset_id = "profile-tabs-{DevblocksPlatform::strAlphaNum($context,'','_')}"}
<form id="profileTabsConfig{$uniqid}" action="{devblocks_url}{/devblocks_url}" method="POST">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="configTabsSaveJson">
	<input type="hidden" name="context" value="{$context}">
	
	<div style="margin-bottom:10px;">
		<button type="button" class="cerb-add-tab-trigger" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="0" data-edit="context:{$context}"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
	</div>
	
	<fieldset class="peek">
		<legend>Display these tabs on this record type:</legend>

		<table class="cerb-table cerb-sortable">
			{foreach from=$profile_tabs item=profile_tab name=tabs}
				<tbody class="cerb-sort-item">
					<tr>
						<td>
							<span class="cerb-icons cerb-icon-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:0.5em;"></span>
						</td>
						<td>
							<div style="display:inline-block;border:1px solid var(--cerb-color-background-contrast-200);border-radius:5px 5px 0 0;padding:5px 10px;">
								<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="{$profile_tab->id}" data-edit="true"><b>{$profile_tab->name}</b></a>
								<input type="hidden" name="profile_tabs[]" value="{$profile_tab->id}">
							</div>
						</td>
						<td>
							{$profile_tab->getExtension()->manifest->name}
						</td>
						<td>
							{$profile_tab->options_kata|truncate:128}
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
	let $frm = $('#profileTabsConfig{$uniqid}');
	let $sortable = $frm.find('.cerb-sortable');

	Devblocks.formDisableSubmit($frm);

	// Peeks

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			let $item = $(this).closest('tbody.cerb-sort-item');
			$item.find('a > b').text(e.label);
		})
		.on('cerb-peek-deleted', function(e) {
			e.stopPropagation();
			$(this).closest('tbody.cerb-sort-item').remove();

			let $tabs = $('#{$tabset_id}');
			$tabs.tabs('option', 'active', -1);
		})
		;
	
	$frm.find('.cerb-add-tab-trigger')
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
				.attr('data-context', '{CerberusContexts::CONTEXT_PROFILE_TAB}')
				.attr('data-context-id', e.id)
				.attr('data-edit', 'true')
				.append($('<b/>').text(e.label))
				.appendTo($div)
				;

			$('<input/>')
				.attr('type', 'hidden')
				.attr('name', 'profile_tabs[]')
				.val(e.id)
				.appendTo($td)
				;

			$('<td/>').appendTo($tr);
			$('<td/>').appendTo($tr);

			$sortable.append($sort_item);
			$sortable.sortable('refresh');

			// Add a new tab
			let $tabs = $('#{$tabset_id}');
			let $this = $(this);

			let $new_tab = $('<li/>');
			$new_tab.append($('<a/>').attr('href',e.tab_url).attr('draggable','false').text(e.label));

			$new_tab.insertBefore($tabs.find('.ui-tabs-nav > li:last'));

			$tabs.tabs('refresh');

			$this.effect('transfer', { to:$new_tab, className:'effects-transfer' }, 500, function() { });

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
		genericAjaxPost($frm, '', null, function() {
			e.stopPropagation();
			document.location.reload();
		});
	});
});
</script>