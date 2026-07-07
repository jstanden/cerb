{$uniqid = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="worklistsConfig{$uniqid}">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.worklists'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.add'|devblocks_translate|capitalize}</label>
			<select name="add_context">
				<option value="">- {'common.choose'|devblocks_translate|lower} -</option>
				{foreach from=$contexts item=mft key=mft_id}
				{if $mft->hasOption('workspace')}
				<option value="{$mft_id}" data-cerb-ui-icon="{$mft->params.icon|default:'collection'}">{$mft->name}</option>
				{/if}
				{/foreach}
			</select>
		</div>

		<div class="cerb-ui-form--field">
			<div class="cerb-u-flex cerb-u-flex-column cerb-u-gap-2" data-cerb-worklists-container>
				{foreach from=$worklists item=worklist name=worklists key=worklist_id}
				<div class="cerb-worklist-row cerb-u-flex cerb-u-items-center cerb-u-gap-2" data-cerb-worklist-row>
					<span class="cerb-icons cerb-icon-move cerb-u-cursor-move cerb-u-flex-shrink-0" title="Drag to rearrange" data-cerb-worklist-handle></span>
					<input type="hidden" name="ids[]" value="{$worklist->id}">
					<input type="text" class="cerb-u-flex-1" name="names[]" value="{$worklist->name}">
					<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-text-muted cerb-u-fs-n1 cerb-u-flex-shrink-0">
						{if isset($contexts.{$worklist->context})}
						<span class="cerb-icons cerb-icon-{$contexts.{$worklist->context}->params.icon|default:'collection'}"></span>{$contexts.{$worklist->context}->name}
						{/if}
					</span>
					<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-u-flex-shrink-0" data-cerb-worklist-delete title="{'common.delete'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-trash"></span></button>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $panel = $('#worklistsConfig{$uniqid}');
	const $container = $panel.find('[data-cerb-worklists-container]');
	const $select = $panel.find('select[name=add_context]');

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($container.get(0), { items: '[data-cerb-worklist-row]', handle: '[data-cerb-worklist-handle]' });

	const addRow = function(contextId, name, label, icon) {
		const $row = $('<div class="cerb-worklist-row cerb-u-flex cerb-u-items-center cerb-u-gap-2" data-cerb-worklist-row></div>');

		$('<span class="cerb-icons cerb-icon-move cerb-u-cursor-move cerb-u-flex-shrink-0" data-cerb-worklist-handle title="Drag to rearrange"></span>').appendTo($row);
		$('<input type="hidden" name="ids[]">').val(contextId).appendTo($row);
		$('<input type="text" class="cerb-u-flex-1" name="names[]">').val(name).appendTo($row);

		const $meta = $('<span class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-text-muted cerb-u-fs-n1 cerb-u-flex-shrink-0"></span>');
		$('<span class="cerb-icons"></span>').addClass('cerb-icon-' + (icon || 'collection')).appendTo($meta);
		$meta.append(document.createTextNode(label));
		$meta.appendTo($row);

		$('<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-u-flex-shrink-0" data-cerb-worklist-delete title="Delete"><span class="cerb-icons cerb-icon-trash"></span></button>').appendTo($row);

		$row.appendTo($container);
		$row.find('input:text:first').select().focus();
	};

	if(window.CerbUI && CerbUI.SelectMenu) {
		const menu = new CerbUI.SelectMenu($select.get(0), {
			onSelect: function(value, text, option) {
				if(value == '')
					return;

				addRow(value, text, text, option ? option.dataset.cerbUiIcon : 'collection');
				menu.setValue('');
			}
		});
	} else {
		$select.on('change', function() {
			const value = $select.val();

			if(value == '')
				return;

			const $opt = $select.find(':selected');
			addRow(value, $opt.text(), $opt.text(), $opt.data('cerb-ui-icon'));
			$select.val('');
		});
	}

	$container.on('click', '[data-cerb-worklist-delete]', function(e) {
		e.stopPropagation();

		const $row = $(this).closest('[data-cerb-worklist-row]');

		CerbUI.Confirm.open({
			title: 'Delete worklist',
			body: 'Are you sure you want to permanently delete this worklist?',
			onConfirm: function() {
				$row.remove();
			}
		});
	});
});
</script>
