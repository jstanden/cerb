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
				<label class="cerb-ui-form--label">{'dashboard.columns'|devblocks_translate|capitalize}</label>
				{* Sections (base + one collapsible group per custom fieldset) are built by the picker JS below *}
				<div class="cerb-columns-picker" data-cerb-columns-root></div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $columnsRoot = $config.find('[data-cerb-columns-root]');

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

	// ── Columns picker: a base group (drag-reorderable) plus one collapsible group per custom fieldset ──
	// Every checkbox posts to the flat params[columns][] list; the grouping is display-only, and DOM order
	// (base first, then fieldsets) is the saved column order.

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

	// opts: { label, columns, fieldset (bool), hint }
	var buildColumnSection = function(opts) {
		var isFieldset = !!opts.fieldset;
		var $section = $(isFieldset ? '<details open/>' : '<div/>')
			.addClass('cerb-columns-section' + (isFieldset ? ' cerb-u-mt-2' : ''))
			.attr('data-cerb-section', '');

		var $header = $(isFieldset ? '<summary/>' : '<div/>')
			.addClass('cerb-columns-section--header cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mb-1')
			.attr('style', 'font-weight:600;' + (isFieldset ? 'cursor:pointer;' : ''));

		if(isFieldset)
			$header.append($('<span/>').addClass('cerb-icons cerb-icon-collection'));

		$header.append($('<span/>').text(opts.label || ''));

		if(opts.hint)
			$header.append($('<span/>').addClass('cerb-u-text-muted cerb-u-fs-n1').text(opts.hint));

		$header.append($('<span/>').addClass('cerb-u-text-muted cerb-u-fs-n1 cerb-u-ml-auto').attr('data-cerb-count', ''));
		$header.append($('<button/>')
			.attr({ 'type': 'button', 'title': 'Select all' })
			.addClass('cerb-ui-selectall')
			.attr('data-cerb-toggleall', '')
			.append($('<span/>').addClass('cerb-icons cerb-icon-checked'))
		);

		var $grid = $('<div/>').addClass('cerb-columns cerb-ui-tile-grid');
		if(!isFieldset) $grid.attr('data-cerb-columns-base', '');
		(opts.columns || []).forEach(function(f) { $grid.append(buildColumnCell(f)); });

		return $section.append($header).append($grid);
	};

	var refreshSection = function(section) {
		var $section = $(section);
		var $cbs = $section.find('input.cerb-columns-cb');
		var n = 0;
		$cbs.each(function() {
			$(this).closest('.cerb-columns-cell').toggleClass('is-selected', this.checked);
			if(this.checked) n++;
		});
		$section.find('[data-cerb-count]').text($cbs.length ? (n + ' / ' + $cbs.length) : '');
		var allSel = $cbs.length && n === $cbs.length;
		var $toggle = $section.find('[data-cerb-toggleall]');
		$toggle.find('.cerb-icons').attr('class', 'cerb-icons ' + (allSel ? 'cerb-icon-checked' : 'cerb-icon-unchecked'));
		$toggle.attr('title', allSel ? 'Clear all' : 'Select all');
	};

	var renderColumns = function(grouped) {
		$columnsRoot.empty();

		if(!grouped || 'object' != typeof(grouped))
			return;

		$columnsRoot.append(buildColumnSection({
			label: grouped.base_label || '{'common.fields'|devblocks_translate|capitalize}',
			hint: '(drag to reorder)',
			columns: grouped.base || []
		}));

		(grouped.fieldsets || []).forEach(function(fs) {
			$columnsRoot.append(buildColumnSection({ fieldset: true, label: fs.name, columns: fs.columns || [] }));
		});

		$columnsRoot.find('[data-cerb-section]').each(function() { refreshSection(this); });

		// Only base columns are drag-reorderable (their DOM order = the saved column order)
		var baseGrid = $columnsRoot.find('[data-cerb-columns-base]').get(0);
		if(baseGrid && window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(baseGrid, { grid: true, helper: 'clone' });
	};

	// Each section owns its count + select-all/clear toggle
	$columnsRoot.on('change', 'input.cerb-columns-cb', function() {
		refreshSection($(this).closest('[data-cerb-section]').get(0));
	});

	$columnsRoot.on('click', '[data-cerb-toggleall]', function(e) {
		e.preventDefault(); // don't also collapse/expand the <details> summary
		e.stopPropagation();
		var section = $(this).closest('[data-cerb-section]').get(0);
		var $cbs = $(section).find('input.cerb-columns-cb');
		var target = !$cbs.toArray().every(function(cb) { return cb.checked; });
		$cbs.prop('checked', target);
		refreshSection(section);
	});

	renderColumns({$columns_json nofilter});

	$select.on('change', function(e) {
		var ctx = $select.val();

		// Update editors
		worklistSqs.forEach(function(sq) { sq.setContext(ctx); });

		if(0 == ctx.length) {
			renderColumns(null);
			return;
		}

		Devblocks.getSpinner().appendTo($columnsRoot.empty());

		genericAjaxGet('', 'c=profiles&a=invoke&module=profile_tab&action=getContextColumnsJson&context=' + encodeURIComponent(ctx), function(json) {
			renderColumns(json);
		});
	});
});
</script>
