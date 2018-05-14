<div id="profileWidget{$widget->id}">
	<div style="margin:0px 10px 10px 10px;">
		<div style="display:flex;flex-flow:row wrap;align-items:flex-start;">
		{foreach from=$properties item=v key=k name=props}
			<div style="flex:1 1 200px;margin:2px 5px;text-overflow:ellipsis;">
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			</div>
		{/foreach}
		</div>
		
		{include file="devblocks:cerberusweb.core::internal/peek/peek_search_buttons.tpl"}
	</div>
	
	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}
	
	{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#profileWidget{$widget->id}');
	var $properties = $widget.find('> div:first');
	
	$properties.find('.cerb-peek-trigger').cerbPeekTrigger();
	$properties.find('.cerb-search-trigger').cerbSearchTrigger();
});
</script>