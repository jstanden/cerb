{if !empty($properties)}
{foreach from=$properties item=cfset_props}
<fieldset class="properties">
	<legend>{$cfset_props.model->name}</legend>
	
	<div style="padding:0px 10px;display:flex;flex-flow:row wrap;">
	
	{foreach from=$cfset_props.properties item=v key=k name=cfset_fields}
		<div style="flex:1 1 200px;margin:2px 5px;text-overflow:ellipsis;">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
		</div>
	{/foreach}
	
	</div>
</fieldset>
{/foreach}
{/if}
