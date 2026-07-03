	<div class="cerb-uiref-component" id="map">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-map"></span>Map</div>

		{* Self-contained SVG region/point map (choropleth + POIs); no d3/topojson. Decodes TopoJSON, projects
		   with Web Mercator or AlbersUsa, and does pan/zoom + selection itself. Pulls the real map resources. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">World countries (Web Mercator) &mdash; population choropleth + capital cities. Scroll to zoom, drag to pan, click a country/city.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-map" id="uiref-map-world" style="max-width:640px;"
					data-regions-url="{devblocks_url}c=ui&a=resource&key=map.world.countries{/devblocks_url}"
					data-points-url="{devblocks_url}c=ui&a=resource&key=mapPoints.worldCapitalCities{/devblocks_url}"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// `map` mirrors the parsed map KATA; geometry sources are a URL to fetch or a pre-loaded object.
new CerbUI.Map(el, {
	regions: '/…c=ui&a=resource&key=map.world.countries',        // TopoJSON (decoded client-side)
	points:  '/…c=ui&a=resource&key=mapPoints.worldCapitalCities', // GeoJSON points
	map: {
		projection: { type: 'mercator', scale: 90, center: { longitude: 0, latitude: 7 } },
		regions: {
			fill:  { choropleth: { property: 'pop_est', classes: 8 } },   // or color_key / color_map
			label: { title: 'name', properties: { name: { label: 'Country' },
				pop_est: { label: 'Population', format: 'number' } } },
		},
		points: { size: { default: 2 }, fill: { default: '#646464' },
			label: { title: 'name', properties: { adm0name: { label: 'Country' } } } },
	},
});{/literal}</pre>
			</div>
		</div>

		{* AlbersUsa composite: lower-48 conic + Alaska & Hawaii insets, each with its own clip window *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">United States (AlbersUsa) &mdash; note the Alaska &amp; Hawaii insets; color-map fill + state capitals.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-map" id="uiref-map-usa" style="max-width:640px;"
					data-regions-url="{devblocks_url}c=ui&a=resource&key=map.country.usa.states{/devblocks_url}"
					data-points-url="{devblocks_url}c=ui&a=resource&key=mapPoints.usaStateCapitals{/devblocks_url}"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Map(el, {
	regions: '/…c=ui&a=resource&key=map.country.usa.states',
	points:  '/…c=ui&a=resource&key=mapPoints.usaStateCapitals',
	map: {
		projection: { type: 'albersUsa', scale: 650 },     // AK/HI insets are automatic
		regions: { fill: { color_map: { property: 'name',
			colors: { California: 'cornflowerblue', Texas: 'orangered' } } } },
		points:  { size: { default: 2 }, fill: { default: '#646464' } },
	},
});
// Events: el.addEventListener('cerb-ui-map:click', e =&gt; e.detail); // {feature_type, properties, point}{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.Map))
		return;
{literal}
	const world = document.getElementById('uiref-map-world');
	if(world) {
		new CerbUI.Map(world, {
			regions: world.dataset.regionsUrl,
			points: world.dataset.pointsUrl,
			map: {
				projection: { type: 'mercator', scale: 90, center: { longitude: 0, latitude: 7 } },
				regions: {
					fill: { choropleth: { property: 'pop_est', classes: 8 } },
					label: { title: 'name', properties: {
						name: { label: 'Country' },
						pop_est: { label: 'Population', format: 'number' },
						lastcensus: { label: 'Last Census' },
					} },
				},
				points: {
					size: { default: 2 },
					fill: { default: '#646464' },
					label: { title: 'name', properties: { adm0name: { label: 'Country' } } },
				},
			},
		});
	}

	const usa = document.getElementById('uiref-map-usa');
	if(usa) {
		new CerbUI.Map(usa, {
			regions: usa.dataset.regionsUrl,
			points: usa.dataset.pointsUrl,
			map: {
				projection: { type: 'albersUsa', scale: 650 },
				regions: {
					fill: { color_map: { property: 'name', colors: { California: 'cornflowerblue', Texas: 'orangered' } } },
					label: { title: 'name_en', properties: { region_sub: { label: 'Region' }, postal: {}, iso_3166_2: {} } },
				},
				points: { size: { default: 2 }, fill: { default: '#646464' } },
			},
		});
	}
{/literal}
})();
</script>
