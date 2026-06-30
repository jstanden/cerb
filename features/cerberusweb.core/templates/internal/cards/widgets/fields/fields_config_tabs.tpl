{$widget_uniqid = uniqid('cardWidgetConfig')}
<div class="cerb-tabs" style="{if !$widget || !$widget->extension_params.context}display:none;{/if}">
	<ul>
		<li><a href="#{$widget_uniqid}TabFields">{'common.fields'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabOptions">{'common.options'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabToolbar">{'common.toolbar'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabSearchButtons">{'common.search'|devblocks_translate|capitalize} (Deprecated)</a>
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

	<div id="{$widget_uniqid}TabSearchButtons">
		<table cellpadding="3" cellspacing="0" width="100%">
			<thead>
				<tr>
					<td></td>
					<td><b>Record type:</b></td>
					<td><b>Search query to count:</b></td>
				</tr>
			</thead>
			
			{foreach from=$search_buttons item=search_button}
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<button type="button" data-cerb-button="search_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
					</td>
					<td width="1%" nowrap="nowrap" valign="top">
						<select class="cerb-search-context" name="params[search][context][]">
							{foreach from=$search_contexts item=search_context}
							<option value="{$search_context->id}" {if $search_context->id == $search_button.context}selected="selected"{/if}>{$search_context->name}</option>
							{/foreach}
						</select>
						<br>
						<input type="text" name="params[search][label_singular][]" value="{$search_button.label_singular}" style="width:95%;border-color:rgb(200,200,200);" placeholder="(singular label; optional)">
						<br>
						<input type="text" name="params[search][label_plural][]" value="{$search_button.label_plural}" style="width:95%;border-color:rgb(200,200,200);" placeholder="(plural label; optional)">
					</td>
					<td width="98%" valign="top">
						<textarea name="params[search][query][]" class="placeholders" style="width:100%;height:60px;">{$search_button.query}</textarea>
					</td>
				</tr>
			</tbody>
			{/foreach}
			
			<tbody class="cerb-placeholder" style="display:none;">
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<button type="button" data-cerb-button="search_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
					</td>
					<td width="1%" nowrap="nowrap" valign="top">
						<select class="cerb-search-context" name="params[search][context][]">
							{foreach from=$search_contexts item=search_context}
							<option value="{$search_context->id}">{$search_context->name}</option>
							{/foreach}
						</select>
						<br>
						<input type="text" name="params[search][label_singular][]" style="width:95%;border-color:rgb(200,200,200);" placeholder="(singular label; optional)">
						<br>
						<input type="text" name="params[search][label_plural][]" style="width:95%;border-color:rgb(200,200,200);" placeholder="(plural label; optional)">
					</td>
					<td width="98%" valign="top">
						<textarea name="params[search][query][]" class="placeholders" style="width:100%;height:60px;"></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		
		<button type="button" class="cerb-placeholder-add"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {

	// Search
	
	var $tab_search = $('#{$widget_uniqid}TabSearchButtons');
	
	var $tab_search_template = $tab_search.find('tbody.cerb-placeholder').detach();
	var $tab_search_table = $tab_search.find('> table:first');

	$tab_search.find('[data-cerb-button=search_remove]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('tbody').remove();
	});

	$tab_search.find('button.cerb-placeholder-add').on('click', function(e) {
		e.stopPropagation();
		var $clone = $tab_search_template.clone();
		
		$clone
			.show()
			.removeClass('cerb-placeholder')
			.appendTo($tab_search_table)
			;

		$clone.find('[data-cerb-button=search_remove]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('tbody').remove();
		});
	});
	
	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($tab_search.find('> table').get(0), {
			items: 'tbody',
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