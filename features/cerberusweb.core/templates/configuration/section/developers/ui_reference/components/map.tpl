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
				<pre data-cerb-uiref-source>{literal}// ── Construct (all options; the live example passes only what it needs) ──────────────
const map = new CerbUI.Map(el, {
	// Geometry sources — each is a URL to fetch OR a pre-loaded object (TopoJSON or GeoJSON).
	regions:                '/…c=ui&a=resource&key=map.world.countries',        // region geometry
	points:                 '/…c=ui&a=resource&key=mapPoints.worldCapitalCities', // GeoJSON points
	regionProperties:       urlOrObject,     // optional { joinValue: {prop:…} } resource (joined on)
	regionPropertiesInline: {…},             // optional inline props merged OVER the resource
	pointsInline:           { type: 'FeatureCollection', features: [] },  // optional; concatenated
	click: { enabled: false, c: 'profiles', a: 'invokeWidget', widget_id: 0 }, // mapClicked round-trip
	width:  600,                             // viewBox width  (default 600)
	height: 325,                             // viewBox height (default 325)

	map: {   // == the parsed $map KATA (DevblocksUiMap::parse); production passes it through verbatim
		projection: { type: 'mercator', scale: 90, center: { longitude: 0, latitude: 7 } }, // or albersUsa
		regions: {
			fill:  { choropleth: { property: 'pop_est', classes: 8 } },   // or color_key / color_map
			label: { title: 'name', properties: { name: { label: 'Country' },
				pop_est: { label: 'Population', format: 'number' } } },
		},
		points: { size: { default: 2 }, fill: { default: '#646464' },
			label: { title: 'name', properties: { adm0name: { label: 'Country' } } } },
	},
});

// ── Methods ──────────────────────────────────────────────────────────────────────────
map.getView();          // → { center:{longitude,latitude}, scale } — on-screen center + effective
                        //   scale (base × zoom); mirrors the live coordinate readout, so an editor
                        //   can capture the framing after a pan/zoom. null before the map builds.
map.destroy();          // unbind wheel/pan listeners and empty the element
CerbUI.Map.from(el);    // the instance built on a host element

// ── Events (bubbling; payload in e.detail) ─────────────────────────────────────────────
// cerb-ui-map:click   { feature_type:'region'|'point', properties, point:{x,y} }
// cerb-ui-map:ready   { regions:Number, points:Number }{/literal}</pre>
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
});{/literal}</pre>
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
