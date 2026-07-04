{$widget_uniqid = uniqid('cardWidgetConfig')}
<div class="cerb-tabs" style="{if !$widget || !$widget->extension_params.context}display:none;{/if}">
	<ul>
		<li><a href="#{$widget_uniqid}TabFields">{'common.fields'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabOptions">{'common.options'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabToolbar">{'common.toolbar'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabSearchButtons">{'common.search'|devblocks_translate|capitalize}</a>
	</ul>
	
	<div id="{$widget_uniqid}TabFields">
		{capture assign=fp_scope}#{$widget_uniqid}TabFields{/capture}
		{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields_picker.tpl" uid=$widget_uniqid css_scope=$fp_scope base_label=$context_ext->manifest->name}
	</div>
	
	<div id="{$widget_uniqid}TabOptions">
		{$rf_uid = uniqid()}
		<div class="cerb-ui-form cerb-u-mt-3">
			<div class="cerb-ui-form--field">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle">
						<input type="checkbox" name="params[links][show]" id="rfLinks{$rf_uid}" value="1" {if $widget && $widget->extension_params.links.show}checked="checked"{/if}>
						<span class="cerb-ui-toggle--slider"></span>
					</label>
					<label for="rfLinks{$rf_uid}">Show record links</label>
				</div>
			</div>
			<div class="cerb-ui-form--field">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle">
						<input type="checkbox" name="params[options][show_empty_properties]" id="rfEmpty{$rf_uid}" value="1" {if $widget && $widget->extension_params.options.show_empty_properties}checked="checked"{/if}>
						<span class="cerb-ui-toggle--slider"></span>
					</label>
					<label for="rfEmpty{$rf_uid}">Show empty fields</label>
				</div>
			</div>
		</div>
	</div>

	<div id="{$widget_uniqid}TabToolbar">
		<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Toolbar: <span class="cerb-ui-form--hint">KATA</span></div>
			</div>
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.editor',

			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
			])}

			{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      uri: ai.cerb.toolbarBuilder.interaction
    interaction/menu:
      label: Menu
      uri: ai.cerb.toolbarBuilder.menu
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{* The editor toolbar is the KataEditor's integrated strip below; this hidden <ul> is its host section. *}
			<div data-cerb-toolbar-builder hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>

			<textarea name="params[toolbar_kata]" data-editor-lines="15" spellcheck="false">{if $widget}{$widget->extension_params.toolbar_kata}{/if}</textarea>
		</div>
	</div>

	<div id="{$widget_uniqid}TabSearchButtons" class="cerb-u-mt-3">
		{* One search-button config row: record type (SelectMenu) + labels on the left, a CerbUI.SearchQuery on the right. *}
		{function name=search_button_row context='' label_singular='' label_plural='' query='' contexts=null template=false}
		<div data-cerb-search-row{if $template} data-cerb-search-template hidden{/if} class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-u-flex cerb-u-justify-end">
				<button type="button" data-cerb-search-remove class="cerb-ui-button cerb-ui-button--transparent" title="Remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
			</div>

			<div class="cerb-ui-form">
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.record.type'|devblocks_translate|capitalize}</label>
						<select class="cerb-search-context" name="params[search][context][]">
							{foreach from=$contexts item=search_context}
							<option value="{$search_context->id}" data-cerb-ui-icon="{$search_context->params.icon|default:'collection'}" {if $search_context->id == $context}selected="selected"{/if}>{$search_context->name}</option>
							{/foreach}
						</select>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Singular label <span class="cerb-ui-form--hint">(optional)</span></label>
						<input type="text" name="params[search][label_singular][]" value="{$label_singular}" placeholder="(singular label)">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Plural label <span class="cerb-ui-form--hint">(optional)</span></label>
						<input type="text" name="params[search][label_plural][]" value="{$label_plural}" placeholder="(plural label)">
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Search query to count</label>
					<textarea name="params[search][query][]" class="placeholders" rows="1" spellcheck="false">{$query}</textarea>
				</div>
			</div>
		</div>
		{/function}

		<div class="cerb-ui-form" data-cerb-search-rows>
			{foreach from=$search_buttons item=search_button}
			{call name=search_button_row context=$search_button.context label_singular=$search_button.label_singular label_plural=$search_button.label_plural query=$search_button.query contexts=$search_contexts}
			{/foreach}
		</div>

		{* Hidden template row, cloned when adding a new search button *}
		{call name=search_button_row contexts=$search_contexts template=true}

		<div class="cerb-u-mt-2">
			<button type="button" data-cerb-search-add class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-plus"></span> Add search button</button>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {

	// Search buttons

	const $tab_search = $('#{$widget_uniqid}TabSearchButtons');
	const $search_rows = $tab_search.find('[data-cerb-search-rows]');
	const $search_template = $tab_search.find('[data-cerb-search-template]').detach().removeAttr('data-cerb-search-template hidden');

	// Enhance one row: record-type SelectMenu (icons) + a CerbUI.SearchQuery whose autocomplete context
	// follows the selected record type.
	const initSearchRow = function(rowEl) {
		if(!window.CerbUI)
			return;

		const $row = $(rowEl);
		const selectEl = $row.find('select.cerb-search-context')[0];
		const queryEl = $row.find('textarea[name="params[search][query][]"]')[0];

		if(selectEl && CerbUI.SelectMenu)
			new CerbUI.SelectMenu(selectEl, { filter: true });

		let sq = null;
		if(queryEl && CerbUI.SearchQuery) {
			const context = selectEl ? selectEl.value : '';
			sq = new CerbUI.SearchQuery(queryEl, {
				context: context,
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource(context)
			});
		}

		// The native <select> stays (SelectMenu just skins it), so its change swaps the query editor's context.
		if(selectEl && sq)
			$(selectEl).on('change', function() { sq.setContext(this.value); });
	};

	$search_rows.find('[data-cerb-search-row]').each(function() { initSearchRow(this); });

	$tab_search.on('click', '[data-cerb-search-remove]', function(e) {
		e.stopPropagation();
		$(this).closest('[data-cerb-search-row]').remove();
	});

	$tab_search.find('[data-cerb-search-add]').on('click', function(e) {
		e.stopPropagation();
		const clone = $search_template.clone()[0];
		$search_rows.append(clone);
		initSearchRow(clone);
	});

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($search_rows.get(0), {
			items: '[data-cerb-search-row]',
			helper: 'clone'
		});
	
	// Toolbar

	var $tab_toolbar = $('#{$widget_uniqid}TabToolbar');
	
	// KataEditor with the toolbar-builder insert menu merged in as a host section.
	var editor = new CerbUI.KataEditor($tab_toolbar.find('textarea[name="params[toolbar_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataToolbar),
		toolbar: {
			sections: [
				$tab_toolbar.find('[data-cerb-toolbar-builder] ul.cerb-ui-toolbar')[0]
			],
			toolbarOpts: {
				caller: { name: 'cerb.toolbar.editor', params: { toolbar: 'cerb.toolbar.cardWidget.recordFields', selected_text: '' } },
				start: function(formData) {
					formData.set('caller[params][selected_text]', editor.getSelectedText());
				},
				done: function(e) {
					e.stopPropagation();
					if(!e.trigger.is('.cerb-bot-trigger'))
						return;
					if(e.eventData.exit === 'return')
						Devblocks.interactionWorkerPostActions(e.eventData, editor);
				}
			}
		}
	});
});
</script>