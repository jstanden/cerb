{if !empty($properties)}
{foreach from=$properties item=cfset_props}
<fieldset class="properties" style="padding:5px 0;border:0;">
	<legend>{$cfset_props.model->name}</legend>
	
	<div style="padding:0px 5px;display:flex;flex-flow:row wrap;">
	
	{foreach from=$cfset_props.properties item=v key=k name=cfset_fields}
	<div style="flex:0 0 200px;text-overflow:ellipsis;">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
	</div>
	{/foreach}
	
	</div>
</fieldset>
{/foreach}
{/if}
