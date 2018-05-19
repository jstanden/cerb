<div id="profileWidget{$widget->id}Fields">
	<div>
		{if $properties}
			<div style="display:flex;flex-flow:row wrap;justify-content:flex-start;">
			{foreach from=$properties item=v key=k name=props}
				<div style="flex:0 0 200px;margin:2px 5px;text-overflow:ellipsis;">
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				</div>
			{/foreach}
			</div>
		{/if}
		
		{if $search_buttons}
		<div class="cerb-search-buttons" style="margin:5px 0px 10px 5px;">
			{foreach from=$search_buttons item=search_button}
			<button type="button" class="cerb-search-trigger" data-context="{$search_button.context}" data-query="{$search_button.query}"><div class="badge-count">{$search_button.count|default:0}</div> {$search_button.label|capitalize}</button>
			{/foreach}
		</div>
		{/if}
	</div>
	
	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}
	
	{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#profileWidget{$widget->id}Fields');
	var $properties = $widget.find('> div:first');
	
	$properties.find('.cerb-peek-trigger').cerbPeekTrigger();
	$properties.find('.cerb-search-trigger').cerbSearchTrigger();
});
</script>