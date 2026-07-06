<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.worklist'|devblocks_translate|capitalize}</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Record type</label>
				<select name="params[context]" data-cerb-worklist-context>
					<option value="">({'common.choose'|devblocks_translate|lower})</option>
					{foreach from=$context_mfts item=context_mft}
					<option value="{$context_mft->id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $widget->extension_params.context == $context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
					{/foreach}
				</select>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Filter using required query</label>
				<div class="cerb-ui-searchquery" data-cerb-searchquery>
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea name="params[query_required]" class="cerb-ui-searchquery--input" rows="1" autocomplete="off" spellcheck="false">{$widget->extension_params.query_required}</textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Default query</label>
				<div class="cerb-ui-searchquery" data-cerb-searchquery>
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea name="params[query]" class="cerb-ui-searchquery--input" rows="1" autocomplete="off" spellcheck="false">{$widget->extension_params.query}</textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Records per page</label>
					<input type="text" name="params[render_limit]" value="{$widget->extension_params.render_limit|default:5}" class="placeholders" style="width:6em;" autocomplete="off" spellcheck="false">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.color'|devblocks_translate|capitalize}</label>
					<div><input type="text" name="params[header_color]" value="{$widget->extension_params.header_color|default:'#6a87db'}" class="color-picker"></div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-form--label" style="margin:0;">{'dashboard.columns'|devblocks_translate|capitalize}</label>
					<span class="cerb-u-text-muted cerb-u-fs-n1" data-cerb-columns-count></span>
					<button type="button" class="cerb-ui-selectall" data-cerb-columns-toggleall title="Select all"><span class="cerb-icons cerb-icon-checked"></span></button>
				</div>
				<div class="cerb-columns cerb-ui-tile-grid" data-cerb-columns>
					{foreach from=$columns item=column}
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-columns-cell cerb-ui-tile-grid--cell{if $column.is_selected} is-selected{/if}" data-token="{$column.key}">
						<input type="checkbox" class="cerb-columns-cb cerb-u-flex-shrink-0" name="params[columns][]" value="{$column.key}"{if $column.is_selected} checked="checked"{/if}>
						<span class="cerb-columns-cell--label cerb-u-truncate">{$column.label}</span>
					</label>
					{/foreach}
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $columns = $config.find('[data-cerb-columns]');
	var $columnsCount = $config.find('[data-cerb-columns-count]');
	var $columnsToggleAll = $config.find('[data-cerb-columns-toggleall]');

	// Record type — SelectMenu (type-to-filter + per-type icons). Keeps the native <select>, so its change
	// event still drives the query editors + columns list below.
	if(window.CerbUI && CerbUI.SelectMenu)
		$config.find('select[data-cerb-worklist-context]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

	$config.find('input:text.color-picker').each(function() {
		new CerbUI.ColorPicker(this, {
			palette: ['#4065f1','#6A87DB','#9a9a9a','#CF2C1D','#FEAF03','#57970A','#852ee0','#626c70']
		});
	});

	var worklistSqs = [];
	if(window.CerbUI && CerbUI.SearchQuery) {
		$config.find('.cerb-ui-searchquery[data-cerb-searchquery]').each(function() {
			var sq = new CerbUI.SearchQuery(this, {
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource('{$widget->extension_params.context}'),
				context: '{$widget->extension_params.context}',
			});
			var acBtn = this.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
			worklistSqs.push(sq);
		});
	}

	// ── Columns picker (a fields_picker-style tile grid: dim unselected, count + select-all, drag-reorder) ──

	var buildColumnCell = function(field) {
		var $cell = $('<label/>')
			.addClass('cerb-ui-tile cerb-ui-tile--block cerb-columns-cell cerb-ui-tile-grid--cell')
			.attr('data-token', field.key);
		var $cb = $('<input/>')
			.attr('type', 'checkbox')
			.addClass('cerb-columns-cb cerb-u-flex-shrink-0')
			.attr('name', 'params[columns][]')
			.attr('value', field.key);
		if(field.is_selected) { $cb.prop('checked', true); $cell.addClass('is-selected'); }
		$cell.append($cb).append($('<span/>').addClass('cerb-columns-cell--label cerb-u-truncate').text(field.label));
		return $cell;
	};

	var refreshColumnsUI = function() {
		var $cbs = $columns.find('input.cerb-columns-cb');
		var n = 0;
		$cbs.each(function() {
			$(this).closest('.cerb-columns-cell').toggleClass('is-selected', this.checked);
			if(this.checked) n++;
		});
		$columnsCount.text($cbs.length ? (n + ' / ' + $cbs.length) : '');
		var allSel = $cbs.length && n === $cbs.length;
		$columnsToggleAll.find('.cerb-icons').attr('class', 'cerb-icons ' + (allSel ? 'cerb-icon-checked' : 'cerb-icon-unchecked'));
		$columnsToggleAll.attr('title', allSel ? 'Clear all' : 'Select all');
	};

	var ensureColumnsSortable = function() {
		if(!(window.CerbUI && CerbUI.Sortable)) return;
		var inst = CerbUI.Sortable.from($columns.get(0));
		if(inst) inst.refresh();
		else new CerbUI.Sortable($columns.get(0), { grid: true, helper: 'clone' });
	};

	$columns.on('change', 'input.cerb-columns-cb', refreshColumnsUI);

	$columnsToggleAll.on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $cbs = $columns.find('input.cerb-columns-cb');
		var target = !$cbs.toArray().every(function(cb) { return cb.checked; });
		$cbs.prop('checked', target);
		refreshColumnsUI();
	});

	refreshColumnsUI();
	ensureColumnsSortable();

	$select.on('change', function(e) {
		var ctx = $select.val();

		// Update editors
		worklistSqs.forEach(function(sq) { sq.setContext(ctx); });

		if(0 == ctx.length) {
			$columns.empty();
			refreshColumnsUI();
			return;
		}

		Devblocks.getSpinner().appendTo($columns.empty());

		genericAjaxGet('', 'c=profiles&a=invoke&module=profile_tab&action=getContextColumnsJson&context=' + encodeURIComponent(ctx), function(json) {
			$columns.empty();

			if('object' == typeof(json) && json.length > 0) {
				for(let idx in json)
					$columns.append(buildColumnCell(json[idx]));
			}

			refreshColumnsUI();
			ensureColumnsSortable();
		});
	});
});
</script>
