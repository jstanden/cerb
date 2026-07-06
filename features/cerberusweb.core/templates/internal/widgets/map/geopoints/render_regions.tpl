{$map_divid = uniqid('map_')}

{* CerbUI.Map renderer (successor to the d3.v5 + topojson.v3 renderer). All projection/decode/zoom/choropleth
   logic lives in cerb-ui/map.js; here we hand it the parsed $map config as JSON plus resource fetch URLs. The
   component builds its own toolbar/legend/label/coordinate chrome. *}
<div class="cerb-ui-map" id="{$map_divid}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	if(!(window.CerbUI && CerbUI.Map))
		return;

	var el = document.getElementById('{$map_divid}');
	if(!el)
		return;

	var config = {
		map: {$map|json_encode nofilter}
	};

	{if $map.resource.name}
	config.regions = '{devblocks_url}c=ui&a=resource&key={$map.resource.name}{/devblocks_url}?v={$map.resource.updated_at}';
	{/if}
	{if $map.regions.properties.resource.name}
	config.regionProperties = '{devblocks_url}c=ui&a=resource&key={$map.regions.properties.resource.name}{/devblocks_url}?v={$map.regions.properties.resource.updated_at}';
	{/if}
	{if $map.points.resource.name}
	config.points = '{devblocks_url}c=ui&a=resource&key={$map.points.resource.name}{/devblocks_url}?v={$map.points.resource.updated_at}';
	{/if}
	{if $points_json}
	config.pointsInline = {$points_json nofilter};
	{/if}
	{if $region_properties_json}
	config.regionPropertiesInline = {$region_properties_json nofilter};
	{/if}
	{if $click_json}
	config.click = {$click_json nofilter};
	{/if}

	new CerbUI.Map(el, config);
});
</script>
