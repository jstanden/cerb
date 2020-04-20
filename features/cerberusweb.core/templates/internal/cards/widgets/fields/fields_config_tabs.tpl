{$widget_uniqid = uniqid('cardWidgetConfig')}
<div class="cerb-tabs" style="{if !$widget->extension_params.context}display:none;{/if}">
	<ul>
		<li><a href="#{$widget_uniqid}TabFields">{'common.fields'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabOptions">{'common.options'|devblocks_translate|capitalize}</a>
		<li><a href="#{$widget_uniqid}TabSearchButtons">{'common.search'|devblocks_translate|capitalize}</a>
	</ul>
	
	<div id="{$widget_uniqid}TabFields">
		<fieldset class="peek black">
			<legend style="cursor:pointer;" onclick="$(this).closest('fieldset').find('input:checkbox').trigger('click');">{$context_ext->manifest->name}</legend>
			
			<div style="display:flex;flex-flow:row wrap;">
				{foreach from=$properties item=property key=property_key}
				<div class="cerb-sort-item" style="flex:0 0 33%;">
					<label><input type="checkbox" name="params[properties][0][]" value="{$property_key}" {if is_array($widget->extension_params.properties.0) && in_array($property_key, $widget->extension_params.properties.0)}checked="checked"{/if}> {$property.label}</label>
				</div>
				{/foreach}
			</div>
		</fieldset>
		
		{foreach from=$properties_custom_fieldsets item=$custom_fieldset key=custom_fieldset_id}
		<fieldset class="peek black">
			<legend style="cursor:pointer;" onclick="$(this).closest('fieldset').find('input:checkbox').trigger('click');">{$custom_fieldset.model->name}</legend>
			
			<div style="display:flex;flex-flow:row wrap;">
				{foreach from=$custom_fieldset.properties item=property key=property_key}
				<div style="flex:0 0 33%;">
					<label><input type="checkbox" name="params[properties][{$custom_fieldset_id}][]" value="{$property_key}" {if is_array($widget->extension_params.properties.$custom_fieldset_id) && in_array($property_key, $widget->extension_params.properties.$custom_fieldset_id)}checked="checked"{/if}> {$property.label}</label>
				</div>
				{/foreach}
			</div>
		</fieldset>
		{/foreach}
	</div>
	
	<div id="{$widget_uniqid}TabOptions">
		<div>
			<label>
				<input type="checkbox" name="params[links][show]" value="1" {if $widget->extension_params.links.show}checked="checked"{/if}> Show record links
			</label>
		</div>
		<div>
			<label>
				<input type="checkbox" name="params[options][show_empty_properties]" value="1" {if $widget->extension_params.options.show_empty_properties}checked="checked"{/if}> Show empty fields
			</label>
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
						<button type="button" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus"></span></button>
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
						<button type="button" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus"></span></button>
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
		
		<button type="button" class="cerb-placeholder-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
	</div>
</div>

<script type="text/javascript">
$(function() {
	// Fields
	var $tab_fields = $('#{$widget_uniqid}TabFields');

	$tab_fields.find('fieldset:first > div:first').sortable({
		tolerance: 'pointer',
		forceHelperSize: true,
		forcePlaceholderSize: true,
		items: 'div.cerb-sort-item',
		opacity: 0.7
	});
	
	// Search
	
	var $tab_search = $('#{$widget_uniqid}TabSearchButtons');
	
	var $tab_search_template = $tab_search.find('tbody.cerb-placeholder').detach();
	var $tab_search_table = $tab_search.find('> table:first');
	
	$tab_search.find('button.cerb-placeholder-add').on('click', function(e) {
		var $clone = $tab_search_template.clone();
		
		$clone
			.show()
			.removeClass('cerb-placeholder')
			.appendTo($tab_search_table)
			;
		
		$clone.find('.cerb-template-trigger')
			.cerbTemplateTrigger()
			;
	});
	
	$tab_search.find('> table').sortable({
		tolerance: 'pointer',
		placeholder: 'ui-state-highlight',
		forceHelperSize: true,
		forcePlaceholderSize: true,
		items: 'tbody',
		helper: 'clone',
		opacity: 0.7
	});
});
</script>