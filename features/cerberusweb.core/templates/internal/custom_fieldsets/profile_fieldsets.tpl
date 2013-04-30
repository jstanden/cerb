{if !empty($properties)}
{foreach from=$properties item=cfset_props}
<fieldset class="properties">
	<legend>{$cfset_props.model->name}</legend>
	
	<div style="margin-left:15px;">
	
	{foreach from=$cfset_props.properties item=v key=k name=cfset_fields}
		<div class="property">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
		</div>
		
		{if $smarty.foreach.cfset_fields.iteration % 3 == 0 && !$smarty.foreach.cfset_fields.last}
			<br clear="all">
		{/if}
	{/foreach}
	
	</div>
</fieldset>
{/foreach}
{/if}
