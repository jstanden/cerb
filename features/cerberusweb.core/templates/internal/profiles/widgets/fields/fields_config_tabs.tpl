<div class="cerb-tabs" style="{if !$widget->extension_params.context}display:none;{/if}">
	<ul>
		<li><a href="#widget{$widget->id}TabFields">{'common.fields'|devblocks_translate|capitalize}</a>
		<li><a href="#widget{$widget->id}TabOptions">{'common.options'|devblocks_translate|capitalize}</a>
	</ul>
	
	<div id="widget{$widget->id}TabFields">
		<fieldset class="peek black">
			<legend style="cursor:pointer;" onclick="$(this).closest('fieldset').find('input:checkbox').trigger('click');">{$context_ext->manifest->name}</legend>
			
			<div style="display:flex;flex-flow:row wrap;">
				{foreach from=$properties item=property key=property_key}
				<div class="cerb-sort-item" style="flex:0 0 200px;">
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
				<div style="flex:0 0 200px;">
					<label><input type="checkbox" name="params[properties][{$custom_fieldset_id}][]" value="{$property_key}" {if is_array($widget->extension_params.properties.$custom_fieldset_id) && in_array($property_key, $widget->extension_params.properties.$custom_fieldset_id)}checked="checked"{/if}> {$property.label}</label>
				</div>
				{/foreach}
			</div>
		</fieldset>
		{/foreach}
	</div>
	
	<div id="widget{$widget->id}TabOptions">
		<div>
			<label>
				<input type="checkbox" name="params[links][show]" value="1" {if $widget->extension_params.links.show}checked="checked"{/if}> Show record links
			</label>
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	// Fields
	
	var $tab_fields = $('#widget{$widget->id}TabFields');

	$tab_fields.find('fieldset:first > div:first').sortable({
		tolerance: 'pointer',
		placeholder: 'ui-state-highlight',
		forceHelperSize: true,
		forcePlaceholderSize: true,
		items: 'div.cerb-sort-item',
		helper: 'clone',
		opacity: 0.7
	});
});
</script>