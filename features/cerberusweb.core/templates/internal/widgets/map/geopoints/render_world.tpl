<div id="widget{$widget->id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadScripts([
		'/resource/devblocks.core/js/d3/d3.v5.min.js',
		'/resource/devblocks.core/js/d3/topojson.v3.min.js'
	], function() {
		try {
			var widget = d3.select('#widget{$widget->id}');
			
			var countries = {
				"-99": {
					"name": "N. Cyprus",
					"iso_a3": "CYN"
				},
				"004": {
					"name": "Afghanistan",
					"iso_a3": "AFG"
				},
				"024": {
					"name": "Angola",
					"iso_a3": "AGO"
				},
				"008": {
					"name": "Albania",
					"iso_a3": "ALB"
				},
				"784": {
					"name": "United Arab Emirates",
					"iso_a3": "ARE"
				},
				"032": {
					"name": "Argentina",
					"iso_a3": "ARG"
				},
				"051": {
					"name": "Armenia",
					"iso_a3": "ARM"
				},
				"010": {
					"name": "Antarctica",
					"iso_a3": "ATA"
				},
				"260": {
					"name": "Fr. S. Antarctic Lands",
					"iso_a3": "ATF"
				},
				"036": {
					"name": "Australia",
					"iso_a3": "AUS"
				},
				"040": {
					"name": "Austria",
					"iso_a3": "AUT"
				},
				"031": {
					"name": "Azerbaijan",
					"iso_a3": "AZE"
				},
				"108": {
					"name": "Burundi",
					"iso_a3": "BDI"
				},
				"056": {
					"name": "Belgium",
					"iso_a3": "BEL"
				},
				"204": {
					"name": "Benin",
					"iso_a3": "BEN"
				},
				"854": {
					"name": "Burkina Faso",
					"iso_a3": "BFA"
				},
				"050": {
					"name": "Bangladesh",
					"iso_a3": "BGD"
				},
				"100": {
					"name": "Bulgaria",
					"iso_a3": "BGR"
				},
				"044": {
					"name": "Bahamas",
					"iso_a3": "BHS"
				},
				"070": {
					"name": "Bosnia and Herz.",
					"iso_a3": "BIH"
				},
				"112": {
					"name": "Belarus",
					"iso_a3": "BLR"
				},
				"084": {
					"name": "Belize",
					"iso_a3": "BLZ"
				},
				"068": {
					"name": "Bolivia",
					"iso_a3": "BOL"
				},
				"076": {
					"name": "Brazil",
					"iso_a3": "BRA"
				},
				"096": {
					"name": "Brunei",
					"iso_a3": "BRN"
				},
				"064": {
					"name": "Bhutan",
					"iso_a3": "BTN"
				},
				"072": {
					"name": "Botswana",
					"iso_a3": "BWA"
				},
				"140": {
					"name": "Central African Rep.",
					"iso_a3": "CAF"
				},
				"124": {
					"name": "Canada",
					"iso_a3": "CAN"
				},
				"756": {
					"name": "Switzerland",
					"iso_a3": "CHE"
				},
				"152": {
					"name": "Chile",
					"iso_a3": "CHL"
				},
				"156": {
					"name": "China",
					"iso_a3": "CHN"
				},
				"384": {
					"name": "Côte d'Ivoire",
					"iso_a3": "CIV"
				},
				"120": {
					"name": "Cameroon",
					"iso_a3": "CMR"
				},
				"180": {
					"name": "Dem. Rep. Congo",
					"iso_a3": "COD"
				},
				"178": {
					"name": "Congo",
					"iso_a3": "COG"
				},
				"170": {
					"name": "Colombia",
					"iso_a3": "COL"
				},
				"188": {
					"name": "Costa Rica",
					"iso_a3": "CRI"
				},
				"192": {
					"name": "Cuba",
					"iso_a3": "CUB"
				},
				"196": {
					"name": "Cyprus",
					"iso_a3": "CYP"
				},
				"203": {
					"name": "Czech Rep.",
					"iso_a3": "CZE"
				},
				"276": {
					"name": "Germany",
					"iso_a3": "DEU"
				},
				"262": {
					"name": "Djibouti",
					"iso_a3": "DJI"
				},
				"208": {
					"name": "Denmark",
					"iso_a3": "DNK"
				},
				"214": {
					"name": "Dominican Rep.",
					"iso_a3": "DOM"
				},
				"012": {
					"name": "Algeria",
					"iso_a3": "DZA"
				},
				"218": {
					"name": "Ecuador",
					"iso_a3": "ECU"
				},
				"818": {
					"name": "Egypt",
					"iso_a3": "EGY"
				},
				"232": {
					"name": "Eritrea",
					"iso_a3": "ERI"
				},
				"732": {
					"name": "W. Sahara",
					"iso_a3": "SAH"
				},
				"724": {
					"name": "Spain",
					"iso_a3": "ESP"
				},
				"233": {
					"name": "Estonia",
					"iso_a3": "EST"
				},
				"231": {
					"name": "Ethiopia",
					"iso_a3": "ETH"
				},
				"246": {
					"name": "Finland",
					"iso_a3": "FIN"
				},
				"242": {
					"name": "Fiji",
					"iso_a3": "FJI"
				},
				"238": {
					"name": "Falkland Is.",
					"iso_a3": "FLK"
				},
				"250": {
					"name": "France",
					"iso_a3": "FRA"
				},
				"266": {
					"name": "Gabon",
					"iso_a3": "GAB"
				},
				"826": {
					"name": "United Kingdom",
					"iso_a3": "GBR"
				},
				"268": {
					"name": "Georgia",
					"iso_a3": "GEO"
				},
				"288": {
					"name": "Ghana",
					"iso_a3": "GHA"
				},
				"324": {
					"name": "Guinea",
					"iso_a3": "GIN"
				},
				"270": {
					"name": "Gambia",
					"iso_a3": "GMB"
				},
				"624": {
					"name": "Guinea-Bissau",
					"iso_a3": "GNB"
				},
				"226": {
					"name": "Eq. Guinea",
					"iso_a3": "GNQ"
				},
				"300": {
					"name": "Greece",
					"iso_a3": "GRC"
				},
				"304": {
					"name": "Greenland",
					"iso_a3": "GRL"
				},
				"320": {
					"name": "Guatemala",
					"iso_a3": "GTM"
				},
				"328": {
					"name": "Guyana",
					"iso_a3": "GUY"
				},
				"340": {
					"name": "Honduras",
					"iso_a3": "HND"
				},
				"191": {
					"name": "Croatia",
					"iso_a3": "HRV"
				},
				"332": {
					"name": "Haiti",
					"iso_a3": "HTI"
				},
				"348": {
					"name": "Hungary",
					"iso_a3": "HUN"
				},
				"360": {
					"name": "Indonesia",
					"iso_a3": "IDN"
				},
				"356": {
					"name": "India",
					"iso_a3": "IND"
				},
				"372": {
					"name": "Ireland",
					"iso_a3": "IRL"
				},
				"364": {
					"name": "Iran",
					"iso_a3": "IRN"
				},
				"368": {
					"name": "Iraq",
					"iso_a3": "IRQ"
				},
				"352": {
					"name": "Iceland",
					"iso_a3": "ISL"
				},
				"376": {
					"name": "Israel",
					"iso_a3": "ISR"
				},
				"380": {
					"name": "Italy",
					"iso_a3": "ITA"
				},
				"388": {
					"name": "Jamaica",
					"iso_a3": "JAM"
				},
				"400": {
					"name": "Jordan",
					"iso_a3": "JOR"
				},
				"392": {
					"name": "Japan",
					"iso_a3": "JPN"
				},
				"398": {
					"name": "Kazakhstan",
					"iso_a3": "KAZ"
				},
				"404": {
					"name": "Kenya",
					"iso_a3": "KEN"
				},
				"417": {
					"name": "Kyrgyzstan",
					"iso_a3": "KGZ"
				},
				"116": {
					"name": "Cambodia",
					"iso_a3": "KHM"
				},
				"410": {
					"name": "Korea",
					"iso_a3": "KOR"
				},
				"414": {
					"name": "Kuwait",
					"iso_a3": "KWT"
				},
				"418": {
					"name": "Lao PDR",
					"iso_a3": "LAO"
				},
				"422": {
					"name": "Lebanon",
					"iso_a3": "LBN"
				},
				"430": {
					"name": "Liberia",
					"iso_a3": "LBR"
				},
				"434": {
					"name": "Libya",
					"iso_a3": "LBY"
				},
				"144": {
					"name": "Sri Lanka",
					"iso_a3": "LKA"
				},
				"426": {
					"name": "Lesotho",
					"iso_a3": "LSO"
				},
				"440": {
					"name": "Lithuania",
					"iso_a3": "LTU"
				},
				"442": {
					"name": "Luxembourg",
					"iso_a3": "LUX"
				},
				"428": {
					"name": "Latvia",
					"iso_a3": "LVA"
				},
				"504": {
					"name": "Morocco",
					"iso_a3": "MAR"
				},
				"498": {
					"name": "Moldova",
					"iso_a3": "MDA"
				},
				"450": {
					"name": "Madagascar",
					"iso_a3": "MDG"
				},
				"484": {
					"name": "Mexico",
					"iso_a3": "MEX"
				},
				"807": {
					"name": "Macedonia",
					"iso_a3": "MKD"
				},
				"466": {
					"name": "Mali",
					"iso_a3": "MLI"
				},
				"104": {
					"name": "Myanmar",
					"iso_a3": "MMR"
				},
				"499": {
					"name": "Montenegro",
					"iso_a3": "MNE"
				},
				"496": {
					"name": "Mongolia",
					"iso_a3": "MNG"
				},
				"508": {
					"name": "Mozambique",
					"iso_a3": "MOZ"
				},
				"478": {
					"name": "Mauritania",
					"iso_a3": "MRT"
				},
				"454": {
					"name": "Malawi",
					"iso_a3": "MWI"
				},
				"458": {
					"name": "Malaysia",
					"iso_a3": "MYS"
				},
				"516": {
					"name": "Namibia",
					"iso_a3": "NAM"
				},
				"540": {
					"name": "New Caledonia",
					"iso_a3": "NCL"
				},
				"562": {
					"name": "Niger",
					"iso_a3": "NER"
				},
				"566": {
					"name": "Nigeria",
					"iso_a3": "NGA"
				},
				"558": {
					"name": "Nicaragua",
					"iso_a3": "NIC"
				},
				"528": {
					"name": "Netherlands",
					"iso_a3": "NLD"
				},
				"578": {
					"name": "Norway",
					"iso_a3": "NOR"
				},
				"524": {
					"name": "Nepal",
					"iso_a3": "NPL"
				},
				"554": {
					"name": "New Zealand",
					"iso_a3": "NZL"
				},
				"512": {
					"name": "Oman",
					"iso_a3": "OMN"
				},
				"586": {
					"name": "Pakistan",
					"iso_a3": "PAK"
				},
				"591": {
					"name": "Panama",
					"iso_a3": "PAN"
				},
				"604": {
					"name": "Peru",
					"iso_a3": "PER"
				},
				"608": {
					"name": "Philippines",
					"iso_a3": "PHL"
				},
				"598": {
					"name": "Papua New Guinea",
					"iso_a3": "PNG"
				},
				"616": {
					"name": "Poland",
					"iso_a3": "POL"
				},
				"630": {
					"name": "Puerto Rico",
					"iso_a3": "PRI"
				},
				"408": {
					"name": "Dem. Rep. Korea",
					"iso_a3": "PRK"
				},
				"620": {
					"name": "Portugal",
					"iso_a3": "PRT"
				},
				"600": {
					"name": "Paraguay",
					"iso_a3": "PRY"
				},
				"275": {
					"name": "Palestine",
					"iso_a3": "PSX"
				},
				"634": {
					"name": "Qatar",
					"iso_a3": "QAT"
				},
				"642": {
					"name": "Romania",
					"iso_a3": "ROU"
				},
				"643": {
					"name": "Russia",
					"iso_a3": "RUS"
				},
				"646": {
					"name": "Rwanda",
					"iso_a3": "RWA"
				},
				"682": {
					"name": "Saudi Arabia",
					"iso_a3": "SAU"
				},
				"729": {
					"name": "Sudan",
					"iso_a3": "SDN"
				},
				"686": {
					"name": "Senegal",
					"iso_a3": "SEN"
				},
				"090": {
					"name": "Solomon Is.",
					"iso_a3": "SLB"
				},
				"694": {
					"name": "Sierra Leone",
					"iso_a3": "SLE"
				},
				"222": {
					"name": "El Salvador",
					"iso_a3": "SLV"
				},
				"706": {
					"name": "Somalia",
					"iso_a3": "SOM"
				},
				"688": {
					"name": "Serbia",
					"iso_a3": "SRB"
				},
				"728": {
					"name": "S. Sudan",
					"iso_a3": "SDS"
				},
				"740": {
					"name": "Suriname",
					"iso_a3": "SUR"
				},
				"703": {
					"name": "Slovakia",
					"iso_a3": "SVK"
				},
				"705": {
					"name": "Slovenia",
					"iso_a3": "SVN"
				},
				"752": {
					"name": "Sweden",
					"iso_a3": "SWE"
				},
				"748": {
					"name": "Swaziland",
					"iso_a3": "SWZ"
				},
				"760": {
					"name": "Syria",
					"iso_a3": "SYR"
				},
				"148": {
					"name": "Chad",
					"iso_a3": "TCD"
				},
				"768": {
					"name": "Togo",
					"iso_a3": "TGO"
				},
				"764": {
					"name": "Thailand",
					"iso_a3": "THA"
				},
				"762": {
					"name": "Tajikistan",
					"iso_a3": "TJK"
				},
				"795": {
					"name": "Turkmenistan",
					"iso_a3": "TKM"
				},
				"626": {
					"name": "Timor-Leste",
					"iso_a3": "TLS"
				},
				"780": {
					"name": "Trinidad and Tobago",
					"iso_a3": "TTO"
				},
				"788": {
					"name": "Tunisia",
					"iso_a3": "TUN"
				},
				"792": {
					"name": "Turkey",
					"iso_a3": "TUR"
				},
				"158": {
					"name": "Taiwan",
					"iso_a3": "TWN"
				},
				"834": {
					"name": "Tanzania",
					"iso_a3": "TZA"
				},
				"800": {
					"name": "Uganda",
					"iso_a3": "UGA"
				},
				"804": {
					"name": "Ukraine",
					"iso_a3": "UKR"
				},
				"858": {
					"name": "Uruguay",
					"iso_a3": "URY"
				},
				"840": {
					"name": "United States",
					"iso_a3": "USA"
				},
				"860": {
					"name": "Uzbekistan",
					"iso_a3": "UZB"
				},
				"862": {
					"name": "Venezuela",
					"iso_a3": "VEN"
				},
				"704": {
					"name": "Vietnam",
					"iso_a3": "VNM"
				},
				"548": {
					"name": "Vanuatu",
					"iso_a3": "VUT"
				},
				"887": {
					"name": "Yemen",
					"iso_a3": "YEM"
				},
				"710": {
					"name": "South Africa",
					"iso_a3": "ZAF"
				},
				"894": {
					"name": "Zambia",
					"iso_a3": "ZMB"
				},
				"716": {
					"name": "Zimbabwe",
					"iso_a3": "ZWE"
				}
			};
			
			var width = 600,
				height = 325,
				centered;
				
			var projection = d3.geoNaturalEarth1()
				// World
				.scale(110)
				.translate([width/2-25, height/2])
				// United States
				//.scale(600)
				//.translate([width/2+800, height/2+400])
				// Europe
				//.scale(500)
				//.translate([width/2-150, height/2+450])
				;
				
			var pointPath = d3.geoPath()
				.projection(projection)
				.pointRadius(5)
				;
			
			var path = d3.geoPath()
				.projection(projection)
				;

			var svg = widget.append('svg:svg')
				.attr('viewBox', '0 0 ' + width + ' ' + height)
				;
				
			svg.append('rect')
				.style('fill', 'white')
				.attr('width', '100%')
				.attr('height', '100%')
				.style('.pointer-events', 'all')
				.on('click', clickedCountry)
				;
				
			var g = svg.append('g');
			
			var label = widget.append('div')
				.style('font-weight', 'bold')
				.style('margin', '5px')
				;
			
			d3.json('{devblocks_url}c=resource&p=cerberusweb.core&f=maps/world.json{/devblocks_url}?v={$smarty.const.APP_BUILD}').then(function(world) {
				g.append('g')
					.style('fill', '#aaa')
				.selectAll('path')
					.data(topojson.feature(world, world.objects.countries).features)
				.enter().append('path')
					.attr('d', path)
					/*
					.attr('fill', function(d, i) {
						if(d.properties.ISO_A2 == 'US') {
							return 'blue';
						} else if (d.properties.ISO_A2 == 'DE') {
							return 'red';
						}
						return '#aaa';
					})
					*/
					.on('click.zoom', clickedCountry)
				;
				
				g.append('path')
					.datum(topojson.mesh(world, world.objects.countries, function(a,b) {
							return a !== b;
					}))
					.attr('fill', 'none')
					.attr('stroke', 'white')
					.attr('stroke-width', '0.5px')
					//.attr('stroke-linejoin', 'round')
					//.attr('stroke-linecap', 'round')
					//.attr('pointer-events', 'none')
					.attr('d', path)
					;
				
				var points = {json_encode($points) nofilter};
				
				for(series_key in points.objects) {
					g.append('g')
						.selectAll('.point')
							.data(topojson.feature(points, points.objects[series_key]).features)
						.enter().append('path')
							.attr('fill', 'red')
							.attr('stroke', 'black')
							.attr('stroke-width', '.5px')
							.attr('class', 'point')
							//.style('pointer-events', 'none')
							.attr('d', pointPath)
							.on('click.zoom', clickedPOI)
						;
				}
			});
			
			function clickedPOI(d, i) {
				var x, y, k;
				
				if(d && centered !== d) {
					var centroid = path.centroid(d);
					x = centroid[0];
					y = centroid[1];
					k = 2;
					centered = d;
					
					label.text(JSON.stringify(d.properties));
					
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
				}
				
				var selected_index = i;
				
				g.transition()
					.duration(750)
					.attr('transform', 'translate(' + width/2 + ',' + height/2 + ')scale(' + k + ')translate(' + -x + ',' + -y + ')')
					.style('stroke-width', 1.5/k + 'px')
					;
			}
			
			function clickedCountry(d, i) {
				var x, y, k;
				
				if(d && centered !== d) {
					var centroid = path.centroid(d);
					x = centroid[0];
					y = centroid[1];
					k = 2;
					centered = d;
					
					label.text(countries[d.id].name);
					
				} else {
					x = width / 2;
					y = height / 2;
					k = 1;
					centered = null;
					label.text('');
				}
				
				var selected_index = i;
				
				g.selectAll('path')
					.each(function(d, i) {
						var selected = d === centered;
						
						if(selected) {
							d3.select(this)
								.style('fill', 'orange')
								;
						} else {
							d3.select(this)
								.style('fill', null)
								;
						}
						return selected;
					})
					;
					
				g.transition()
					.duration(750)
					.attr('transform', 'translate(' + width/2 + ',' + height/2 + ')scale(' + k + ')translate(' + -x + ',' + -y + ')')
					.style('stroke-width', 1.5/k + 'px')
					;
			}

		} catch(e) {
			console.error(e);
		}
	});
});
</script>