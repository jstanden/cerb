var cerbAutocompleteColorSuggestions = [
	{
		'caption': 'category10:',
		'snippet': '\${1:category10}@csv: #1f77b4, #ff7f0e, #2ca02c, #d62728, #9467bd, #8c564b, #e377c2, #7f7f7f, #bcbd22, #17becf',
	},
	{
		'caption': 'rainbow6:',
		'snippet': '\${1:rainbow6}@csv: #6e40aa, #ee4395, #ff8c38, #aff05b, #28ea8d, #2f96e0',
	},
	{
		'caption': 'rainbow12:',
		'snippet': '\${1:rainbow12}@csv: #6e40aa, #b83cb0, #f6478d, #ff6956, #f59f30, #c4d93e, #83f557, #38f17a, #19d3b5, #29a0dd, #5069d9, #6e40aa',
	},
	{
		'caption': 'blues5:',
		'snippet': '\${1:blues5}@csv: #08519c, #3182bd, #6baed6, #bdd7e7, #eff3ff',
	},
	{
		'caption': 'blues9:',
		'snippet': '\${1:blues9}@csv: #08306b, #08519c, #2171b5, #4292c6, #6baed6, #9ecae1, #c6dbef, #deebf7, #f7fbff',
	},
	{
		'caption': 'reds5:',
		'snippet': '\${1:reds5}@csv: #a50f15, #de2d26, #fb6a4a, #fcae91, #fee5d9',
	},
	{
		'caption': 'reds9:',
		'snippet': '\${1:reds9}@csv: #67000d, #a50f15, #cb181d, #ef3b2c, #fb6a4a, #fc9272, #fcbba1, #fee0d2, #fff5f0',
	},
	{
		'caption': 'greens5:',
		'snippet': '\${1:greens5}@csv: #006d2c, #31a354, #74c476, #bae4b3, #edf8e9',
	},
	{
		'caption': 'greens9:',
		'snippet': '\${1:greens9}@csv: #00441b, #006d2c, #238b45, #41ab5d, #74c476, #a1d99b, #c7e9c0, #e5f5e0, #f7fcf5',
	},
	{
		'caption': 'grays5:',
		'snippet': '\${1:grays5}@csv: #252525, #636363, #969696, #cccccc, #f7f7f7',
	},
	{
		'caption': 'grays9:',
		'snippet': '\${1:grays9}@csv: #ffffff, #f0f0f0, #d9d9d9, #bdbdbd, #969696, #737373, #525252, #252525, #000000',
	},
];

var cerbAutocompleteSuggestions = {
	kataAutomationEvent: {
		'': [
			{
				'caption': 'automation:',
				'snippet': 'automation/${1:name}:'
			}
		],
		'automation:': [
			{
				'caption': 'uri:',
				'snippet': 'uri: cerb:automation:${1:name}' 
			},
			{
				'caption': 'disabled:',
				'snippet': 'disabled@bool: ${1:yes}'
			},
			'inputs:'
		],
		'automation:uri:': {
			'type': 'cerb-uri',
			'params': {
				'automation': null,
			}
		},
		'automation:inputs:': {
			'type': 'automation-inputs'
		}
	},
	kataAutomationPolicy: {
		'': [
			'commands:',
			'settings:'
		],
		'commands:': [
			'api.command:',
			'data.query:',
			'decrypt.pgp:',
			'email.parse:',
			'encrypt.pgp:',
			'file.read:',
			'file.write:',
			'function:',
			'http.request:',
			'metric.increment:',
			'queue.pop:',
			'queue.push:',
			'record.create:',
			'record.delete:',
			'record.get:',
			'record.search:',
			'record.update:',
			'record.upsert:',
			'storage.delete:',
			'storage.get:',
			'storage.set:'
		],
		'commands:api.command:': [
			'allow@bool: yes',
			{
				'caption': 'deny/name:',
				'snippet': "deny/name@bool: {{inputs.name not in ['\${1:example.api.name}']}}",
				'docHTML': 'Validate API function name'
			},
			'deny@bool: yes'
		],
		'commands:data.query:': [
			'allow@bool: yes',
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{query.type != '\${1:example.type}'}}",
				'docHTML': 'Validate data query type'
			},
			'deny@bool: yes'
		],
		'commands:decrypt.pgp:': [
			'allow@bool: yes',
			'deny@bool: yes'
		],
		'commands:email.parse:': [
			'allow@bool: yes',
			'deny@bool: yes'
		],
		'commands:encrypt.pgp:': [
			'allow@bool: yes',
			'deny@bool: yes'
		],
		'commands:file.read:': [
			'deny@bool: yes',
			{
				'caption': 'deny/uri:',
				'snippet': "deny/uri@bool: {{inputs.uri != 'cerb:attachment:1'}}",
				'docHTML': 'Validate file URI'
			},
			'allow@bool: yes'
		],
		'commands:file.write:': [
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:function:': [
			'deny@bool: yes',
			{
				'caption': 'deny/uri:',
				'snippet': "deny/uri@bool: {{uri != 'cerb:automation:example.name'}}",
				'docHTML': 'Validate function automation URI'
			},
			'allow@bool: yes'
		],
		'commands:http.request:': [
			{
				'caption': 'deny/method:',
				'snippet': "deny/method@bool: {{inputs.method not in ['GET']}}",
				'docHTML': 'Validate HTTP method'
			},
			{
				'caption': 'deny/url:',
				'snippet': "deny/url@bool: {{inputs.url is not prefixed ('https://')}}",
				'docHTML': 'Validate HTTP URL'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:metric.increment:': [
			{
				'caption': 'deny/metric_name:',
				'snippet': "deny/metric_name@bool: {{inputs.metric_name != '${1:your.metric.name}'}}",
				'docHTML': 'Validate metric name',
			},
			'deny@bool: yes',
			'allow@bool: yes',
		],
		'commands:queue.pop:': [
			{
				'caption': 'deny/queue_name:',
				'snippet': "deny/queue_name@bool: {{inputs.queue_name != '${1:your.queue.name}'}}",
				'docHTML': 'Validate queue name'
			},
			'deny@bool: yes',
			'allow@bool: yes',
		],
		'commands:queue.push:': [
			{
				'caption': 'deny/queue_name:',
				'snippet': "deny/queue_name@bool: {{inputs.queue_name != '${1:your.queue.name}'}}",
				'docHTML': 'Validate queue name'
			},
			'deny@bool: yes',
			'allow@bool: yes',
		],
		'commands:record.create:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes',
		],
		'commands:record.delete:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:record.get:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:record.search:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:record.update:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:record.upsert:': [
			{
				'caption': 'deny/type:',
				'snippet': "deny/type@bool: {{inputs.record_type is not record type ('task','ticket')}}",
				'docHTML': 'Validate record type'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:storage.delete:': [
			{
				'caption': 'deny/key:',
				'snippet': "deny/key@bool: {{inputs.key is not prefixed ('key:prefix:')}}",
				'docHTML': 'Validate storage key'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:storage.get:': [
			{
				'caption': 'deny/key:',
				'snippet': "deny/key@bool: {{inputs.key is not prefixed ('key:prefix:')}}",
				'docHTML': 'Validate storage key'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:storage.set:': [
			{
				'caption': 'deny/key:',
				'snippet': "deny/key@bool: {{inputs.key is not prefixed ('key:prefix:')}}",
				'docHTML': 'Validate storage key'
			},
			'deny@bool: yes',
			'allow@bool: yes'
		],
		
		'settings:': [
			'time_limit_ms: 25000'
		]
	},
	kataSchemaChart: {
		'': [
			'axis:',
			'color:',
			'data:',
			'grid:',
			'legend:',
			'tooltip:'
		],

		'axis:': [
			'x:',
			'y:',
			'y2:'
		],
		'axis:x:': [
			'categories@list:',
			'label:',
			'tick:',
			'type:'
		],
		'axis:x:tick:': [
			'format:',
			'fit@bool: no',
			'multiline@bool: no',
			'rotate: -90'
		],
		'axis:x:tick:format:': [
			'date:',
			'duration:',
			'number:'
		],
		'axis:x:tick:format:date:': [
			'pattern:'
		],
		'axis:x:tick:format:date:pattern:': [
			{
				'caption': '(choose date format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3TimeFormat',
				'interaction_params': ''
			}
		],
		'axis:x:tick:format:duration:': [
			'precision@int: 2',
			'unit:'
		],
		'axis:x:tick:format:duration:precision:': [
			'2'
		],
		'axis:x:tick:format:duration:unit:': [
			'milliseconds',
			'seconds',
			'minutes',
		],
		'axis:x:tick:format:number:': [
			'pattern:'
		],
		'axis:x:tick:format:number:pattern:': [
			{
				'caption': '(choose number format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3Format',
				'interaction_params': ''
			}
		],
		'axis:x:type:': [
			'category',
			'linear',
			'timeseries'
		],
		'axis:y:': [
			'label:',
			'tick:',
			'type:'
		],
		'axis:y:tick:': [
			'format:',
			'fit@bool: no',
			'multiline@bool: no',
			'rotate: -90'
		],
		'axis:y:tick:format:': [
			'date:',
			'duration:',
			'number:'
		],
		'axis:y:tick:format:date:': [
			'pattern:'
		],
		'axis:y:tick:format:date:pattern:': [
			{
				'caption': '(choose date format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3TimeFormat',
				'interaction_params': ''
			}
		],
		'axis:y:tick:format:duration:': [
			'precision@int: 2',
			'unit:'
		],
		'axis:y:tick:format:duration:precision:': [
			'2'
		],
		'axis:y:tick:format:duration:unit:': [
			'milliseconds',
			'seconds',
			'minutes',
		],
		'axis:y:tick:format:number:': [
			'pattern:'
		],
		'axis:y:tick:format:number:pattern:': [
			{
				'caption': '(choose number format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3Format',
				'interaction_params': ''
			}
		],
		'axis:y:type:': [
			'category',
			'linear',
			'timeseries'
		],
		'axis:y2:': [
			'label:',
			'tick:',
			'type:'
		],
		'axis:y2:tick:': [
			'format:',
			'fit@bool: no',
			'multiline@bool: no',
			'rotate: -90'
		],
		'axis:y2:tick:format:': [
			'date:',
			'duration:',
			'number:'
		],
		'axis:y2:tick:format:date:': [
			'pattern:'
		],
		'axis:y2:tick:format:date:pattern:': [
			{
				'caption': '(choose date format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3TimeFormat',
				'interaction_params': ''
			}
		],
		'axis:y2:tick:format:duration:': [
			'precision@int: 2',
			'unit:'
		],
		'axis:y2:tick:format:duration:precision:': [
			'2'
		],
		'axis:y2:tick:format:duration:unit:': [
			'milliseconds',
			'seconds',
			'minutes',
		],
		'axis:y2:tick:format:number:': [
			'pattern:'
		],
		'axis:y2:tick:format:number:pattern:': [
			{
				'caption': '(choose number format)',
				'interaction': 'ai.cerb.automationBuilder.autocomplete.d3Format',
				'interaction_params': ''
			}
		],
		'axis:y2:type:': [
			'category',
			'linear',
			'timeseries'
		],
		
		'color:': [
			'patterns:',	
		],
		'color:patterns:': cerbAutocompleteColorSuggestions,
		
		'data:': [
			'series:',
			'stacks:',
			'type:'
		],
		'data:series:': [
			'dataset_name:'
		],
		'data:stacks:': [
			'0@csv: series0, series1'
		],
		'data:type:': [
			'area',
			'area-spline',
			'area-step',
			'bar',
			'donut',
			'gauge',
			'line',
			'pie',
			'scatter',
			'spline',
			'step',
		],
		
		'grid:': [
			'x:',	
			'y:',	
		],
		'grid:x:': [
			'lines:',	
		],
		'grid:x:lines:': [
			'0:',
		],
		'grid:y:': [
			'lines:',
		],
		'grid:y:lines:': [
			'0:',
		],
		
		'legend:': [
			'show@bool: yes',
			'style:',
		],
		'legend:show:': [
			'yes',
			'no',
		],
		'legend:style:': [
			'compact:',
			'table:',
		],
		'legend:style:table:': [
			'data@bool: yes',
			'stats@csv: sum, avg, max, min',
		],
		
		'tooltip:': [
			'grouped@bool: yes',
			'show@bool: yes',
			'ratios@bool: yes',
		],
		'tooltip:grouped:': [
			'yes',
			'no',
		],
		'tooltip:show:': [
			'yes',
			'no',
		],
		
		'*': {
			'data:series:(.*?):y_axis:': [
				'y',
				'y2',
			],
			'data:series:(.*?):y_type:': [
				'area',
				'area-spline',
				'area-step',
				'bar',
				'donut',
				'gauge',
				'line',
				'pie',
				'scatter',
				'spline',
				'step',
			],
			'data:series:(.*?):': [
				'name:',
				'color_pattern:',
				'x_key:',
				'y_axis:',
				'y_type:'
			],
			
			'grid:(x|y):lines:(.*?):position:': [
				'start',
				'end',
			],
			'grid:(x|y):lines:(.*?):': [
				'position:',
				'text:',
				'value:',
			],
		}
	},
	kataSchemaDashboardFilters: {
		'': [
			{
				'caption': 'chooser:',
				'snippet': 'chooser/${1:key}:\n  label: ${2:Chooser:}\n  params:\n    context: ${3: record_type}\n    single@bool: no\n'
			},
			{
				'caption': 'date_range:',
				'snippet': 'date_range/${1:key}:\n  label: ${2:Date:}\n  default: ${3:first day of this month -12 months}\n'
			},
			{
				'caption': 'picklist:',
				'snippet': 'picklist/${1:key}:\n  label: ${2:Picklist:}\n  default: ${3:month}\n  params:\n    options@list:\n      day\n      week\n      month\n      year\n'
			},
			{
				'caption': 'text:',
				'snippet': 'text/${1:key}:\n  label: ${2:Text:}\n  default: ${3:text}\n'
			}
		],
		'chooser:': [
			'label:',
			'default:',
			'params:'
		],
		'chooser:params:': [
			'context:',
			'query@text:',
			'single@bool: no'
		],
		'date_range:': [
			'label:',
			'default:',
			'params:'
		],
		'date_range:params:': [
			{
				'caption': 'presets:',
				'snippet': 'presets:\n  1d:\n    label: 1d\n    query: today to now\n'
			}
		],
		'picklist:': [
			'label:',
			'default:',
			'params:'
		],
		'picklist:params:': [
			'multiple@bool: yes',
			'options@list:'
		],
		'text:': [
			'label:',
			'default:',
			'params:'
		],
		'text:params:': [
			'hidden@bool: yes',
		]
	},
	kataSchemaDataset: {
		'': [
			{
				'caption': 'automation:',
				'snippet': 'automation/${1:series0}:'
			},
			{
				'caption': 'dataQuery:',
				'snippet': 'dataQuery/${1:series0}:'
			},
			{
				'caption': 'manual:',
				'snippet': 'manual/${1:series0}:'
			}
		],
		
		'automation:': [
			{
				'caption': 'uri:',
				'snippet': 'uri: cerb:automation:${1:name}'
			},
			'inputs:'
		],
		'automation:uri:': {
			'type': 'cerb-uri',
			'params': {
				'automation': {
					'triggers': [
						'cerb.trigger.ui.chart.data'
					]
				}
			}
		},
		'automation:inputs:': {
			'type': 'automation-inputs'
		},

		'dataQuery:': [
			{
				'caption': 'query:',
				'snippet': "query@text:\n  "
			},
			{
				'caption': 'query_params:',
				'snippet': "query_params:\n  ${1:key}: ${2:value}"
			},
			'cache_secs: 300',
			{
				'caption': 'key_map:',
				'snippet': "key_map:\n  ${1:key}: ${2:newKey}"
			},
			{
				'caption': 'key_map@csv:',
				'snippet': "key_map@csv: ${1:oldKey1}, ${2:newKey1}, oldKey2, newKey2"
			},
		],
		
		'manual:': [
			{
				'caption': 'data:',
				'snippet': "data:\n  ",
			}
		],
		'manual:data:': [
			'series_name@csv: 1,2,3'
		]
	},
	kataSchemaMap: {
		'': [
			'map:'
		],
		'map:': [
			{
				'caption': 'resource:',
				'snippet': 'resource:\n  uri: cerb:resource:map.world.countries'
			},
			{
				'caption': 'projection:',
				'snippet': 'projection:\n  type: mercator\n  scale: 90\n  center:\n    latitude: 0\n    longitude: 0\n'
			},
			'regions:',
			'points:'
		],

		'map:resource:': [
			'uri:'
		],

		'map:projection:': [
			'type:',
			'scale:',
			'center:',
			'zoom:'
		],
		'map:projection:type:': [
			'mercator',
			'naturalEarth',
			'albersUsa'
		],
		'map:projection:scale:': [
			'90'
		],
		'map:projection:center:': [
			'latitude:',
			'longitude:'
		],
		'map:projection:zoom:': [
			'latitude:',
			'longitude:',
			'scale:'
		],

		'map:regions:': [
			'label:',
			'properties:',
			'filter:',
			'fill:'
		],
		'map:regions:label:': [
			'title:',
			'properties:'
		],
		'map:regions:properties:': [
			{
				'caption': 'join:',
				'snippet': 'join:\n  property: ${1:key_name}\n  #case: upper\n'
			},
			'resource:'
		],
		'map:regions:properties:join': [
			'property:',
			'case:'
		],
		'map:regions:properties:join:case:': [
			'upper',
			'lower'
		],
		'map:regions:properties:resource:': [
			'uri:'
		],
		'map:regions:filter:': [
			{
				'caption': 'is:',
				'snippet': 'property: ${1:key_name}\nis: ${2:value}\n'
			},
			{
				'caption': 'is@list:',
				'snippet': 'property: ${1:key_name}\nis@list:\n    ${2:value1}\n    ${3:value2}\n'
			},
			{
				'caption': 'not:',
				'snippet': 'property: ${1:key_name}\nnot: ${2:value}\n'
			},
			{
				'caption': 'not@list:',
				'snippet': 'property: ${1:key_name}\nnot@list:\n    ${2:value1}\n    ${3:value2}\n'
			}
		],
		'map:regions:fill:': [
			{
				'caption': 'color_key:',
				'snippet': 'color_key:\n  property: ${1:key_name}'
			},
			{
				'caption': 'color_map:',
				'snippet': 'color_map:\n  property: ${1:key_name}\n  colors@list:\n    ${2:key_value}: red'
			},
			{
				'caption': 'choropleth:',
				'snippet': 'choropleth:\n  property: ${1:key_name}\n  classes: 8\n'
			}
		],
		'map:regions:fill:choropleth:': [
			'property:',
			{
				'caption': 'classes:',
				'snippet': 'classes: ${1:8}\n'
			}
		],

		'map:points:': [
			'resource:',
			'label:',
			'filter:',
			'size:',
			'fill:',
			'data:'
		],
		'map:points:resource:': [
			'uri:'
		],
		'map:points:label:': [
			'title:',
			'properties:'
		],
		'map:points:filter:': [
			{
				'caption': 'is:',
				'snippet': 'property: ${1:key_name}\nis: ${2:value}\n'
			},
			{
				'caption': 'is@list:',
				'snippet': 'property: ${1:key_name}\nis@list:\n    ${2:value1}\n    ${3:value2}\n'
			},
			{
				'caption': 'not:',
				'snippet': 'property: ${1:key_name}\nnot: ${2:value}\n'
			},
			{
				'caption': 'not@list:',
				'snippet': 'property: ${1:key_name}\nnot@list:\n    ${2:value1}\n    ${3:value2}\n'
			}
		],
		'map:points:size:': [
			'default:',
			{
				'caption': 'value_map:',
				'snippet': 'value_map:\n  property: ${1:key_name}\n  values:\n    ${2:property_value}: 5.0'
			}
		],
		'map:points:fill:': [
			'default:',
			{
				'caption': 'color_map:',
				'snippet': 'color_map:\n  property: ${1:key_name}\n  colors:\n    ${2:property_value}: #FF0000'
			}
		],
		'map:points:data:': [
			{
				'caption': 'point:',
				'snippet': 'point/name:\n  latitude: 0\n  longitude: 0\n  properties:\n    name: Place name\n'
			}
		]
	},
	kataSchemaMetricDimension: {
		'': [
			{
				'caption': 'extension:',
				'snippet': 'extension/${1:name}:\n'
			},
			{
				'caption': 'number:',
				'snippet': 'number/${1:name}:\n'
			},
			{
				'caption': 'record:',
				'snippet': 'record/${1:name}:\n  record_type: ${2:ticket}\n'
			},
			{
				'caption': 'text:',
				'snippet': 'text/${1:name}:\n'
			}
		],
		'extension:': [

		],
		'record:': [
			{
				'caption': 'record_type:',
				'snippet': 'record_type: ${1:ticket}',
				'score': 2000,
			},
			{
				'caption': 'record_label:',
				'snippet': 'record_label: ${1:_label}'
			}
		],
		'record:record_type:': {
			'type': 'record-type'
		},
		'text:': [

		]
	},
	kataToolbar: {
		'': [
			{
				'caption': 'interaction:',
				'snippet': 'interaction/${1:name}:'
			},
			{
				'caption': 'menu:',
				'snippet': 'menu/${1:name}:'
			}
		],
		'*': {
			'(.*):?interaction:': [
				'after:',
				{
					'caption': 'uri:',
					'snippet': 'uri: cerb:automation:${1:}'
				},
				'label:',
				'icon:',
				'icon_at:',
				'keyboard:',
				'tooltip:',
				{
					'caption': 'hidden:',
					'snippet': 'hidden@bool: ${1:yes}'
				},
				{
					'caption': 'badge:',
					'snippet': 'badge: 123'
				},
				{
					'caption': 'class:',
					'snippet': 'class: action-always-show'
				},
				'inputs:'
			],
			'(.*):?interaction:hidden:': [
				'yes',
				'no',
				{
					'caption': '{{key}}',
					'snippet': '{{${1:key}}}',
				},
				{
					'caption': '{{not key}}',
					'snippet': '{{not ${1:key}}}',
				}
			],
			'(.*):?interaction:icon:': {
				'type': 'icon'
			},
			'(.*):?interaction:icon_at:': [
				'start',
				'end',
			],
			'(.*):?interaction:inputs:': {
				'type': 'automation-inputs'
			},
			'(.*):?interaction:keyboard:': [
				'k',
				'ctrl+k',
				'meta+k',
				'shift+k',
			],
			'(.*):?interaction:uri:': {
				'type': 'cerb-uri',
				'params': {
					'automation': {
						'triggers': [
							'cerb.trigger.interaction.worker'
						]
					}
				}
			},
			'(.*):?menu:': [
				'label:',
				{
					'caption': 'hidden:',
					'snippet': 'hidden@bool: ${1:yes}'
				},
				'icon:',
				'tooltip:',
				'items:'
			],
			'(.*):?menu:items:': [
				{
					'caption': 'interaction:',
					'snippet': 'interaction/${1:name}:'
				},
				{
					'caption': 'menu:',
					'snippet': 'menu/${1:name}:'
				}
			],
		}
	},
	kataSchemaSheet: {
		'': [
			{
				caption: 'layout:',
				snippet: 'layout:\n  style: ${1:table}\n  headings: ${2:true}\n  paging: ${3:true}\n  #title_column: _label\n',
			},
			{
				caption: 'columns:',
				snippet: 'columns:\n',
			},
			{
				caption: 'data:',
				snippet: 'data:\n',
			}
		],
		
		// Layout
		'layout:': [
			{
				caption: 'style:',
				snippet: 'style: ${1:table}'
			},
			{
				caption: 'headings:',
				snippet: 'headings@bool: ${1:yes}'
			},
			{
				caption: 'filtering:',
				snippet: 'filtering@bool: ${1:yes}'
			},
			{
				caption: 'paging:',
				snippet: 'paging@bool: ${1:yes}'
			},
			{
				caption: 'title_column:',
				snippet: 'title_column: ${1:key}'
			},
			{
				caption: 'colors:',
				snippet: 'colors:'
			},
		],
		'layout:colors:': cerbAutocompleteColorSuggestions,
		'layout:style:': [
			{
				'caption': 'table',
				'snippet': 'table',
				'score': 2000,
			},
			{
				'caption': 'fieldsets',
				'snippet': 'fieldsets',
				'score': 1999,
			},
			{
				'caption': 'grid',
				'snippet': 'grid',
				'score': 1998,
			},
			{
				'caption': 'columns',
				'snippet': 'columns',
				'score': 1997,
			}
		],
		'layout:headings:': [
			'true',
			'false'
		],
		'layout:paging:': [
			'true',
			'false'
		],
		
		// Column types
		'columns:': [
			{
				caption: 'card:',
				snippet: 'card/${1:key}:\n  label: ${2:Label}\n  params:\n    #image@bool: yes\n    #bold@bool: yes\n    #underline: false\n'
			},
			{
				caption: 'date:',
				snippet: 'date/${1:key}:\n  label: ${2:Label}\n  params:\n    #format: d-M-Y H:i:s T # See: https://php.net/date\n    #format: r\n    #value: 1577836800\n    #value_key: updated\n'
			},
			{
				caption: 'icon:',
				snippet: 'icon/${1:key}:\n  label: ${2:Icon}\n  params:\n    # See: Setup->Developers->Icon Reference\n    image: ${3:circle-ok}\n'
			},
			{
				caption: 'interaction:',
				snippet: 'interaction/${1:key}:\n  label: ${2:Interaction}\n  params:\n    text: ${3:Link text}\n    uri: cerb:automation:${4:example.interaction.name}\n    #inputs:\n'
			},
			{
				caption: 'link:',
				snippet: 'link/${1:key}:\n  label: ${2:Label}\n  params:\n    href: ${3:/some/path}\n    #href_key: some_key\n    #href_template: /some/path/{{placeholder}}\n    text: ${4:Link text}\n    #text_key: some_key\n'
			},
			{
				caption: 'markdown:',
				snippet: 'markdown/${1:key}:\n  label: ${2:Label}\n  params:\n    #value: literal text\n    #value_key: some_key\n    #value_template: "{{some_key}}"'
			},
			{
				caption: 'search:',
				snippet: 'search/${1:key}:\n  label: ${2:Label}\n  params:\n    context: ticket\n    #context_key: _context\n    query: status:o\n    #query_key: query\n    label: Label\n    #label: count\n'
			},
			{
				caption: 'search_button:',
				snippet: 'search_button/${1:key}:\n  label: ${2:Label}\n  params:\n    context: ticket\n    #context_key: _context\n    query: status:o\n    #query_key: query    #query_template: status:o owner.id:{{id}}\n'
			},
			{
				caption: 'selection:',
				snippet: 'selection/${1:key}:\n  params:\n    value_key: id\n    #value: 123\n    #value_template@raw: {{id}}\n'
			},
			{
				caption: 'slider:',
				snippet: 'slider/${1:key}:\n  label: ${3:Label}\n  params:\n    #value: 75\n    #value_key: some_key\n    #value_template: "{{some_key+50}}"\n    min: 0\n    max: 100\n'
			},
			{
				caption: 'text:',
				snippet: 'text/${1:key}:\n  label: ${2:Label}\n  params:\n    #value: literal text\n    #value_key: some_key\n    #value_template: "{{some_key}}"\n    #bold@bool: yes\n    #value_map:\n      #0: No\n      #1: Yes\n'
			},
			{
				caption: 'time_elapsed:',
				snippet: 'time_elapsed/${1:key}:\n  label: ${2:Label}\n  params:\n    precision@int: ${3:2}\n'
			},
			{
				caption: 'toolbar:',
				snippet: 'toolbar/${1:key}:\n  label: ${2:Label}\n  params:\n    ${3:}\n'
			}
		],
		
		// Text
		'columns:text:': [
			'label:',
			'params:'
		],
		'columns:text:params:': [
			'value:',
			'value_key:',
			'value_template@raw:',
			'value_map:',
			'bold@bool:',
			'icon:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:text:params:bold:': [
			'true',
			'false'
		],
		'columns:text:params:icon:': [
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			}
		],
		'columns:text:params:icon:image:': {
			'type': 'icon'
		},
		// Cards
		'columns:card:': [
			'label:',
			'params:'
		],
		'columns:card:params:': [
			'image@bool:',
			'bold@bool:',
			'underline@bool:',
			'context:',
			'context_key:',
			'context_template@raw:',
			'icon:',
			'id:',
			'id_key:',
			'id_template@raw:',
			'label:',
			'label_key:',
			'label_template@raw:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:card:params:icon:': [
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			}
		],
		'columns:card:params:icon:image:': {
			'type': 'icon'
		},
		'columns:card:params:image:': [
			'yes',
			'no'
		],
		'columns:card:params:bold:': [
			'yes',
			'no'
		],
		'columns:card:params:underline:': [
			'yes',
			'no'
		],
		
		// Dates
		'columns:date:': [
			'label:',
			'params:'
		],
		'columns:date:params:': [
			'value:',
			'format:',
			'value_key:',
			'value_template@raw:',
			'bold@bool:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:date:params:bold:': [
			'yes',
			'no'
		],
		'columns:date:params:format:': [
			'r',
			'Y-m-d H:i:s a'
		],

		// Icon
		'columns:icon:': [
			'label:',
			'params:'
		],
		'columns:icon:params:': [
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			},
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:icon:params:image:': {
			'type': 'icon'
		},
		
		// Interaction
		'columns:interaction:': [
			'label:',
			'params:'
		],
		'columns:interaction:params:': [
			'inputs:',
			'text:',
			'text_key:',
			'text_template@raw:',
			'uri:',
			'uri_key:',
			'uri_template@raw:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:interaction:params:uri:': {
			'type': 'cerb-uri',
			'params': {
				'automation': {
					'triggers': [
						'cerb.trigger.interaction.worker'
					]
				}
			}
		},
		'columns:interaction:params:inputs:': {
			'type': 'automation-inputs'
		},

		// Links
		'columns:link:': [
			'label:',
			'params:'
		],
		'columns:link:params:': [
			'href:',
			'href_key:',
			'href_template@raw:',
			'href_new_tab@bool: yes',
			'icon:',
			'text:',
			'text_key:',
			'text_template@raw:',
			'bold@bool:'
		],
		'columns:link:params:bold:': [
			'yes',
			'no'
		],
		'columns:link:params:icon:': [
			'at:',
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			}
		],
		'columns:link:params:icon:image:': {
			'type': 'icon'
		},
		'columns:link:params:icon:at:': [
			'start',
			'end'
		],

		// Markdown
		'columns:markdown:': [
			'label:',
			'params:'
		],
		'columns:markdown:params:': [
			'value:',
			'value_key:',
			'value_template@raw:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],

		// Search
		'columns:search:': [
			'label:',
			'params:'
		],
		'columns:search:params:': [
			'context:',
			'context_key:',
			'context_template@raw:',
			'query:',
			'query_key:',
			'query_template@raw:',
			'label:',
			'label_key:',
			'label_template@raw:',
			'bold@bool:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:search:params:bold:': [
			'yes',
			'no'
		],
		
		// Search button
		'columns:search_button:': [
			'label:',
			'params:'
		],
		'columns:search_button:params:': [
			'context:',
			'context_key:',
			'context_template@raw:',
			'query:',
			'query_key:',
			'query_template@raw:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		
		// Selection
		'columns:selection:': [
			'params:'
		],
		'columns:selection:params:': [
			'value:',
			'value_key:',
			'value_template@raw: {{id}}',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		
		// Slider
		'columns:slider:': [
			'label:',
			'params:'
		],
		'columns:slider:params:': [
			'value:',
			'value_key:',
			'value_template@raw:',
			'min:',
			'max:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		
		// Time elapsed
		'columns:time_elapsed:': [
			'label:',
			'params:'
		],
		'columns:time_elapsed:params:': [
			'value:',
			'value_key:',
			'value_template@raw:',
			'precision:',
			'bold@bool:',
			'color@raw:',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:time_elapsed:params:bold:': [
			'yes',
			'no'
		],
		
		// Toolbar
		'columns:toolbar:': [
			'label:',
			'params:'
		],
		'columns:toolbar:params:': [
			'kata:'
		],
		'columns:toolbar:params:kata:': [
			{
				'caption': 'interaction:',
				'snippet': 'interaction/${1:name}:'
			},
			{
				'caption': 'menu:',
				'snippet': 'menu/${1:name}:'
			}
		],
		
		// Wildcards
		'*': {
			'columns:toolbar:params:kata:(.*):?interaction:': [
				'after:',
				{
					'caption': 'uri:',
					'snippet': 'uri: cerb:automation:${1:}'
				},
				'label:',
				'icon:',
				'tooltip:',
				{
					'caption': 'hidden:',
					'snippet': 'hidden@bool: ${1:yes}'
				},
				{
					'caption': 'badge:',
					'snippet': 'badge: 123'
				},
				{
					'caption': 'class:',
					'snippet': 'class: some-css-class-name'
				},
				'inputs:'
			],
			'columns:toolbar:params:kata:(.*):?interaction:hidden:': [
				'yes',
				'no',
				{
					'caption': '{{key}}',
					'snippet': '{{${1:key}}}',
				},
				{
					'caption': '{{not key}}',
					'snippet': '{{not ${1:key}}}',
				}
			],
			'columns:toolbar:params:kata:(.*):?interaction:icon:': {
				'type': 'icon'
			},
			'columns:toolbar:params:kata:(.*):?interaction:inputs:': {
				'type': 'automation-inputs'
			},
			'columns:toolbar:params:kata:(.*):?interaction:uri:': {
				'type': 'cerb-uri',
				'params': {
					'automation': {
						'triggers': [
							'cerb.trigger.interaction.worker'
						]
					}
				}
			},
			'columns:toolbar:params:kata:(.*):?menu:': [
				'label:',
				{
					'caption': 'hidden:',
					'snippet': 'hidden@bool: ${1:yes}'
				},
				'icon:',
				'tooltip:',
				'items:'
			],
			'columns:toolbar:params:kata:(.*):?menu:items:': [
				{
					'caption': 'interaction:',
					'snippet': 'interaction/${1:name}:'
				},
				{
					'caption': 'menu:',
					'snippet': 'menu/${1:name}:'
				}
			],
		}
	},
	kataSchemaWorklistExport: {
		'': [
			{
				'caption': 'column:',
				'snippet': 'column/${1:_label}:\n'
			},
		],
		'column:': [
			{
				'caption': 'label:',
				'snippet': 'label: ${1:Label}',
				'score': 2000,
			},
			{
				'caption': 'value:',
				'snippet': 'value@raw: {{${1:_label}}}'
			}
		]
	}
};

let twigAutocompleteSuggestions = {
	snippets: [
		{ value: "{%", meta: "tag" },
		{ value: "{{", meta: "variable" },
		{ value: "do", snippet: "{% do ${1:1 + 2} %}", meta: "snippet" },
		{ value: "for loop", snippet: "{% for ${1:var} in ${2:array} %}\n${3}\n{% endfor %}", meta: "snippet" },
		{ value: "if...else", snippet: "{% if ${1:placeholder} %}${2}{% else %}${3}{% endif %}", meta: "snippet" },
		{ value: "set object value", snippet: "{% set ${1:obj} = dict_set(${1:obj},\"${2:key.path}\",\"${3:value}\") %}", meta: "snippet" },
		{ value: "set variable", snippet: "{% set var = \"${1}\" %}", meta: "snippet" },
		{ value: "spaceless block", snippet: "{% apply spaceless %}\n${1}\n{% endapply %}\n", meta: "snippet" },
		{ value: "verbatim block", snippet: "{% verbatim %}\n${1}\n{% endverbatim %}\n", meta: "snippet" },
		{ value: "with block", snippet: "{% with %}\n${1}\n{% endwith %}\n", meta: "snippet" },
	],

	tags: [
		{ value: "apply", meta: "command" },
		{ value: "do", meta: "command" },
		{ value: "endapply", meta: "command" },
		{ value: "endif", meta: "command" },
		{ value: "endfor", meta: "command" },
		{ value: "endverbatim", meta: "command" },
		{ value: "endwith", meta: "command" },
		{ value: "filter", meta: "command" },
		{ value: "for", meta: "command" },
		{ value: "if", meta: "command" },
		{ value: "set", meta: "command" },
		{ value: "verbatim", meta: "command" },
		{ value: "with", meta: "command" },
	],

	filters: [
		{ value: "abs", meta: "filter" },
		{ value: "alphanum", meta: "filter" },
		{ value: "append", snippet: "append('${1:suffix}', delimiter=', ')", meta: "filter" },
		{ value: "base_convert", snippet: "base_convert(${1:16},${2:10})", meta: "filter" },
		{ value: "base64_decode", meta: "filter" },
		{ value: "base64_encode", meta: "filter" },
		{ value: "base64url_decode", meta: "filter" },
		{ value: "base64url_encode", meta: "filter" },
		{ value: "batch(n,fill)", meta: "filter" },
		{ value: "bytes_pretty()", snippet: "bytes_pretty(${1:2})", meta: "filter" },
		{ value: "capitalize", meta: "filter" },
		{ value: "cerb_translate", meta: "filter" },
		{ value: "column(key)", snippet: "column(\"${1:key}\")", meta: "filter" },
		{ value: "context_alias", snippet: "context_alias", meta: "filter" },
		{ value: "context_name()", snippet: "context_name(\"${1:plural}\")", meta: "filter" },
		{ value: "convert_encoding()", snippet: "convert_encoding(${1:to_charset},${2:from_charset})", meta: "filter" },
		{ value: "csv", snippet: "csv()", meta: "filter" },
		{ value: "date('F d, Y')", meta: "filter" },
		{ value: "date_modify('+1 day')", meta: "filter" },
		{ value: "date_pretty", meta: "filter" },
		{ value: "default('text')", meta: "filter" },
		{ value: "escape", meta: "filter" },
		{ value: "filter(func)", snippet: "filter((v,k) => false)", meta: "filter" },
		{ value: "first", meta: "filter" },
		{ value: "format", meta: "filter" },
		{ value: "hash_hmac()", snippet: "hash_hmac(\"${1:secret key}\",\"${2:sha256}\")", meta: "filter" },
		{ value: "html_to_text(truncate=50000)", meta: "filter" },
		{ value: "indent(marker, fromLine)", meta: "filter" },
		{ value: "join(',')", meta: "filter" },
		{ value: "json_encode", meta: "filter" },
		{ value: "json_pretty", meta: "filter" },
		{ value: "kata_encode", meta: "filter" },
		{ value: "keys", meta: "filter" },
		{ value: "last", meta: "filter" },
		{ value: "length", meta: "filter" },
		{ value: "lower", meta: "filter" },
		{ value: "map(func)", snippet: "map((v,k) => v)", meta: "filter" },
		{ value: "markdown_to_html(is_untrusted=true)", meta: "filter" },
		{ value: "md5", meta: "filter" },
		{ value: "merge", meta: "filter" },
		{ value: "nl2br", meta: "filter" },
		{ value: "number_format(2, '.', ',')", meta: "filter" },
		{ value: "parse_csv", meta: "filter" },
		{ value: "parse_emails", meta: "filter" },
		{ value: "parse_url", meta: "filter" },
		{ value: "parse_user_agent", meta: "filter" },
		{ value: "permalink", meta: "filter" },
		{ value: "quote", meta: "filter" },
		{ value: "raw", meta: "filter" },
		{ value: "reduce(func,initial)", snippet: "reduce((carry,v) => carry + v)", meta: "filter" },
		{ value: "regexp", meta: "filter" },
		{ value: "repeat", meta: "filter" },
		{ value: "replace('this', 'that')", meta: "filter" },
		{ value: "reverse", meta: "filter" },
		{ value: "round(0, 'common')", meta: "filter" },
		{ value: "secs_pretty", meta: "filter" },
		{ value: "sha1", meta: "filter" },
		{ value: "slice", meta: "filter" },
		{ value: "sort", meta: "filter" },
		{ value: "spaceless", meta: "filter" },
		{ value: "split(',')", meta: "filter" },
		{ value: "split_crlf", meta: "filter" },
		{ value: "split_csv", meta: "filter" },
		{ value: "stat(measure='mean',decimals=2)", meta: "filter" },
		{ value: "stat(measure='median',decimals=2)", meta: "filter" },
		{ value: "stat(measure='mode',decimals=2)", meta: "filter" },
		{ value: "stat(measure='stdevp',decimals=2)", meta: "filter" },
		{ value: "stat(measure='stdevs',decimals=2)", meta: "filter" },
		{ value: "stat(measure='varp',decimals=2)", meta: "filter" },
		{ value: "stat(measure='vars',decimals=2)", meta: "filter" },
		{ value: "strip_lines(prefixes='>')", meta: "filter" },
		{ value: "striptags", meta: "filter" },
		{ value: "title", meta: "filter" },
		{ value: "tokenize", meta: "filter" },
		{ value: "trim", meta: "filter" },
		{ value: "truncate(10)", meta: "filter" },
		{ value: "unescape", meta: "filter" },
		{ value: "upper", meta: "filter" },
		{ value: "url_decode", meta: "filter" },
		{ value: "url_decode('json')", meta: "filter" },
		{ value: "url_encode", meta: "filter" },
		{ value: "values", meta: "filter" },
	],

	functions: [
		{ value: "array_column(array,column_key,index_key)", meta: "function" },
		{ value: "array_combine(keys,values)", meta: "function" },
		{ value: "array_count_values(array)", meta: "function" },
		{ value: "array_diff(array1,array2)", meta: "function" },
		{ value: "array_extract_keys(array,keys)", meta: "function" },
		{ value: "array_fill_keys(keys,value)", meta: "function" },
		{ value: "array_intersect(array1,array2)", meta: "function" },
		{ value: "array_matches(values, patterns)", meta: "function" },
		{ value: "array_sort_keys(array)", meta: "function" },
		{ value: "array_sum(array)", meta: "function" },
		{ value: "array_unique(array)", meta: "function" },
		{ value: "array_values(array)", meta: "function" },
		{ value: "attribute(object,attr)", meta: "function" },
		{ value: "cerb_automation(uri,inputs)", meta: "function" },
		{ value: "cerb_avatar_image(context,id,updated)", meta: "function" },
		{ value: "cerb_avatar_url(context,id,updated)", meta: "function" },
		{ value: "cerb_calendar_time_elapsed(calendar,date_from,date_to)", meta: "function" },
		{ value: "cerb_extract_uris(html)", meta: "function" },
		{ value: "cerb_file_url(file_id,full,proxy)", meta: "function" },
		{ value: "cerb_has_priv(priv,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_placeholders_list()", meta: "function" },
		{ value: "cerb_placeholders_list(extract='prefix_')", meta: "function" },
		{ value: "cerb_placeholders_list(extract='prefix_',prefix='new_')", meta: "function" },
		{ value: "cerb_record_readable(record_context,record_id,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_record_writeable(record_context,record_id,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_url('c=controller&a=action&p=param')", meta: "function" },
		{ value: "cycle(position)", meta: "function" },
		{ value: "date(date,timezone)", meta: "function" },
		{ value: "dict_set(obj,keypath,value,delimiter='.')", meta: "function" },
		{ value: "date_lerp(date_range,unit,step,limit)", meta: "function" },
		{ value: "dict_unset(obj,keypaths)", meta: "function" },
		{ value: "dns_get_record(host,type)", meta: "function" },
		{ value: "dns_host_by_ip(ip)", meta: "function" },
		{ value: "json_decode(string)", meta: "function" },
		{ value: "jsonpath_set(json,keypath,value)", meta: "function" },
		{ value: "max(array)", meta: "function" },
		{ value: "min(array)", meta: "function" },
		{ value: "random(values)", meta: "function" },
		{ value: "random_string(length)", meta: "function" },
		{ value: "range(low,high,step)", snippet: "range(${1:low},${2:high},${3:step})", meta: "function" },
		{ value: "regexp_match_all(pattern,text,group)", meta: "function" },
		{ value: "shuffle(array)", meta: "function" },
		{ value: "validate_email(string)", meta: "function" },
		{ value: "validate_number(string)", meta: "function" },
		{ value: "vobject_parse(string)", meta: "function" },
		{ value: "xml_attr(xml,name,default)", meta: "function" },
		{ value: "xml_attrs(xml)", meta: "function" },
		{ value: "xml_decode(string,namespaces)", meta: "function" },
		{ value: "xml_decode(string,namespaces,'html')", meta: "function" },
		{ value: "xml_encode(xml)", meta: "function" },
		{ value: "xml_xpath(xml,path,element)", meta: "function" },
		{ value: "xml_xpath_ns(xml,prefix,ns)", meta: "function" },
		{ value: "xml_tag(xml)", meta: "function" },
	]
};

$.fn.cerbDateInputHelper = function(options) {
	options = (typeof options == 'object') ? options : {};
	
	return this.each(function() {
		var $this = $(this);
		
		$this.datepicker({
			showOn: 'button',
			buttonText: '',
			dateFormat: 'D, d M yy',
			defaultDate: 'D, d M yy',
			numberOfMonths: 1,
			onSelect: function(dateText, inst) {
				inst.input.addClass('changed').focus();
			}
		});
		
		let $icon = $('<span class="glyphicons glyphicons-calendar"/>');
		$this.next('.ui-datepicker-trigger').append($icon);
		
		$this
			.attr('placeholder', '+2 hours; +4 hours @Calendar; Jan 15 2018 2pm; 5pm America/New York')
			.autocomplete({
				delay: 300,
				minLength: 1,
				autoFocus: false,
				source: function(request, response) {
					request.term = request.term.split(' ').pop();;
					
					if(request.term == null)
						return;

					var formData = new FormData();
					formData.set('c', 'internal');
					formData.set('a', 'invoke');
					formData.set('module', 'calendars');
					formData.set('action', 'getDateInputAutoCompleteOptionsJson');
					formData.set('term', request.term);
					
					genericAjaxPost(formData, null, null, function(data) {
						response(data);
					});
				},
				focus: function() {
					return false;
				},
				select: function(event, ui) {
					$(this).addClass('autocomplete_select');
					
					var terms = this.value.split(' ');
					terms.pop();
					terms.push(ui.item.value);
					terms.push('');
					this.value = terms.join(' ');
					$(this).addClass('changed', true);
					return false;
				}
			})
			.data('uiAutocomplete')
				._renderItem = function(ul, item) {
					var $li = $('<li/>')
						.data('ui-autocomplete-item', item)
						.append($('<a></a>').text(item.label))
						.appendTo(ul);
					
					item.value = $li.text();
					
					return $li;
				}
			;
		
		$this
			.on('send', function(e) {
				var $input_date = $(this);
				var val = $input_date.val();
				
				if(!$input_date.is('.changed')) {
					if(e.keydown_event_caller && e.keydown_event_caller.shiftKey && e.keydown_event_caller.ctrlKey && e.keydown_event_caller.which == 13)
						if(options.submit && typeof options.submit == 'function')
							options.submit();
						
					return;
				}
				
				$input_date.autocomplete('close');
				
				// If the date contains any placeholders, don't auto-parse it
				if(-1 !== val.indexOf('{'))
					return;
				
				// Send the text to the server for translation
				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'calendars');
				formData.set('action', 'parseDateJson');
				formData.set('date', val);

				genericAjaxPost(formData, '', '', function(json) {
					if(json == false) {
						// [TODO] Color it red for failed, and display an error somewhere
						$input_date.val('');
					} else {
						$input_date.val(json.to_string);
						$this.trigger('cerb-date-changed');
					}
					
					if(e.keydown_event_caller && e.keydown_event_caller.shiftKey && e.keydown_event_caller.ctrlKey && e.keydown_event_caller.which == 13)
						if(options.submit && typeof options.submit == 'function')
							options.submit();
					
					$input_date.removeClass('changed');
				});
			})
			.blur(function(e) {
				$(this).trigger('send');
			})
			.keydown(function(e) {
				$(this).addClass('changed', true);
				
				// If the worker hit enter and we're not showing an autocomplete menu
				if(e.which == 13) {
					e.preventDefault();

					if($(this).is('.autocomplete_select')) {
						$(this).removeClass('autocomplete_select');
						return false;
					}
					
					$(this).trigger({ type: 'send', 'keydown_event_caller': e });
				}
			})
			;
		
		var $parent = $this.parent();
		
		if($parent.is(':visible')) {
			var width_minus_icons = ($parent.width() - 64);
			var width_relative = Math.floor(100 * (width_minus_icons / $parent.width()));
			$this.css('width', width_relative + '%');
		}
	});
};

var cAjaxCalls = function() {
	this.viewCloseTickets = function(view_id,mode) {
		var formName = 'viewForm'+view_id;
		var $frm = $('#' + formName);

		if(0 === $frm.length)
			return;

		showLoadingPanel();

		var formData = new FormData($frm[0]);

		switch(mode) {
			case 1: // spam
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkSpam');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
			case 2: // delete
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkDeleted');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
			default: // close
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'viewMarkClosed');
				formData.set('view_id', view_id);

				genericAjaxPost(formData, '', '', function() {
					hideLoadingPanel();
					genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
				});
				break;
		}
	};
	
	this.viewAddQuery = function(view_id, query, replace) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);
		formData.set('add_mode', 'query');
		formData.set('replace', replace);
		formData.set('query', query);

		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		});
	};
	
	this.viewAddFilter = function(view_id, field, oper, values, replace) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);
		formData.set('replace', replace);
		formData.set('field', field);
		formData.set('oper', oper);

		Devblocks.objectToFormData(values, formData);

		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh');
			}
		});
	};
	
	this.viewRemoveFilter = function(view_id, fields) {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('id', view_id);

		for(var field in fields) {
			formData.append('field_deletes[]', fields[field]);
		}
		
		genericAjaxPost(formData, null, null, function(o) {
			var $view_filters = $('#viewCustomFilters'+view_id);
			
			if(0 !== $view_filters.length) {
				$view_filters.html(o);
				$view_filters.trigger('view_refresh')
			}
		});
	};
	
	this.viewUndo = function(view_id, is_dismissed) {
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'ticket');
		formData.set('action', 'viewUndo');
		formData.set('view_id', view_id);

		if(is_dismissed) {
			formData.set('clear', '1');
			genericAjaxPost(formData, null, null);

		} else {
			showLoadingPanel();

			genericAjaxPost(formData, null, null, function() {
				hideLoadingPanel();
				genericAjaxGet('view' + view_id,'c=internal&a=invoke&module=worklists&action=refresh&id=' + encodeURIComponent(view_id));
			});
		}
	};

	this.emailAutoComplete = function(sel, options) {
		if(null == options)
			options = { };
		
		if(null == options.delay)
			options.delay = 300;

		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;
		
		if(null != options.multiple && options.multiple) {
			options.source = function (request, response) {
				// From the last comma (if exists)
				var pos = request.term.lastIndexOf(',');
				if(-1 !== pos) {
					// Split at the comma and trim
					request.term = $.trim(request.term.substring(pos+1));
				}
				
				if(0 === request.term.length)
					return;

				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'autocomplete');
				formData.set('context', 'cerberusweb.contexts.address');
				formData.set('term', request.term);

				genericAjaxPost(formData, null, null, function(data) {
					response(data);
				});
			};
			options.select = function(event, ui) {
				var value = $(this).val();
				var pos = value.lastIndexOf(',');
				if(-1 !== pos) {
					$(this).val(value.substring(0,pos)+', '+ui.item.label+', ');
				} else {
					$(this).val(ui.item.label+', ');
				}
				return false;
			};
			
			options.focus = function(event, ui) {
				// Don't replace the textbox value
				return false;
			}
			
		} else {
			options.source = function (request, response) {
				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'autocomplete');
				formData.set('context', 'cerberusweb.contexts.address');
				formData.set('term', request.term);

				genericAjaxPost(formData, null, null, function(data) {
					response(data);
				});
			};
			options.select = function(event, ui) {
				$(this).val(ui.item.label);
				return false;
			};
			
			options.focus = function(event, ui) {
				// Don't replace the textbox value
				return false;
			};
		}
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}

	this.orgAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=profiles&a=invoke&module=org&action=autocomplete';
		
		if(null == options.delay)
			options.delay = 300;
		
		if(null == options.minLength)
			options.minLength = 1;
		
		if(null == options.autoFocus)
			options.autoFocus = true;

		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}
	
	this.countryAutoComplete = function(sel, options) {
		if(null == options) options = { };
		
		options.source = DevblocksAppPath+'ajax.php?c=profiles&a=invoke&module=org&action=autocompleteCountry';
		
		if(null == options.delay)
			options.delay = 300;
		
		if(null == options.minLength)
			options.minLength = 1;

		if(null == options.autoFocus)
			options.autoFocus = true;
		
		var $sel = $(sel);
		
		$sel.autocomplete(options);
	}

	this.chooser = function(button, context, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		var $button = $(button);

		$button.attr('data-field-name', field_name);

		// The <ul> buffer
		var $ul = $button.siblings('ul.chooser-container');
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			var $ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}
		
		// The chooser search button
		$button.click(function(event) {
			var $button = $(this);
			var $ul = $(this).siblings('ul.chooser-container:first');
			
			var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=invoke&module=records&action=chooserOpen&context=' + context,null,true,'90%');
			$chooser.one('chooser_save', function(event) {
				// Add the labels
				for(var idx in event.labels)
					if(0===$ul.find('input:hidden[value="'+event.values[idx]+'"]').length) {
						var $li = $('<li/>').text(event.labels[idx]);
						var $hidden = $('<input type="hidden">').attr('name', field_name + '[]').attr('value',event.values[idx]).appendTo($li);
						var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li); 
						
						if(null != options.style)
							$li.addClass(options.style);
						$ul.append($li);
					}
			});
		});
		
		// Autocomplete
		if(null != options.autocomplete && true == options.autocomplete) {
			
			if(null == options.autocomplete_class) {
				options.autocomplete_class = ''; //'input_search';
			}
			
			var $autocomplete = $('<input type="text" size="45">').addClass(options.autocomplete_class);
			$autocomplete.insertBefore($button);
			
			$autocomplete.autocomplete({
				delay: 250,
				source: DevblocksAppPath+'ajax.php?c=internal&a=invoke&module=records&action=autocomplete&context=' + context,
				minLength: 1,
				focus:function(event, ui) {
					return false;
				},
				autoFocus:true,
				select:function(event, ui) {
					var $this = $(this);
					var $label = ui.item.label;
					var $value = ui.item.value;
					var $ul = $this.siblings('button:first').siblings('ul.chooser-container:first');
					
					if(undefined != $label && undefined != $value) {
						if(0 == $ul.find('input:hidden[value="'+$value+'"]').length) {
							var $li = $('<li/>').text($label);
							var $hidden = $('<input type="hidden">').attr('name', field_name + '[]').attr('title', $label).attr('value', $value).appendTo($li);
							var $a = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
							$ul.append($li);
						}
					}
					
					$this.val('');
					return false;
				}
			});
		}
	}
	
	this.chooserFile = function(button, field_name, options) {
		if(null == field_name)
			field_name = 'context_id';
		
		if(null == options) 
			options = { };
		
		var $button = $(button);

		$button.attr('data-field-name', field_name);

		// The <ul> buffer
		var $ul = $button.next('ul.chooser-container');
		
		if(null == options.single)
			options.single = false;
		
		// Add the container if it doesn't exist
		if(0==$ul.length) {
			var $ul = $('<ul class="bubbles chooser-container"></ul>');
			$ul.insertAfter($button);
		}

		$button.on('cerb-chooser-save', function(event) {
			// If in single-selection mode
			if(options.single)
				$ul.find('li').remove();

			// Add the labels
			for(var idx in event.labels) {
				if (0 === $ul.find('input:hidden[value="' + event.values[idx] + '"]').length) {
					var $label = $('<a href="javascript:;" class="cerb-peek-trigger" data-context="cerberusweb.contexts.attachment" />')
						.attr('data-context-id', event.values[idx])
						.text(event.labels[idx])
						.cerbPeekTrigger()
					;
					var $li = $('<li/>').append($label);
					$('<input type="hidden">').attr('name', field_name + (options.single ? '' : '[]')).attr('value', event.values[idx]).appendTo($li);
					$('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);

					if (null != options.style)
						$li.addClass(options.style);

					$ul.append($li);
				}
			}
		});
		
		// The chooser search button
		$button.click(function(event) {
			var $chooser=genericAjaxPopup('chooser','c=internal&a=invoke&module=records&action=chooserOpenFile&single=' + (options.single ? '1' : '0'),null,true,'750');
			
			$chooser.one('chooser_save', function(event) {
				var new_event = $.Event(event.type, event);
				new_event.type = 'cerb-chooser-save';
				event.stopPropagation();
				$button.triggerHandler(new_event);
				$button.focus();
			});
		});
	}
	
	this.chooserAvatar = function($avatar_chooser, $avatar_image) {
		$avatar_chooser.click(function() {
			var $editor_button = $(this);
			var context = $editor_button.attr('data-context');
			var context_id = $editor_button.attr('data-context-id');
			var image_width = $editor_button.attr('data-image-width');
			var image_height = $editor_button.attr('data-image-height');
			
			var popup_url = 'c=internal&a=invoke&module=records&action=chooserOpenAvatar&context='
				+ encodeURIComponent(context) 
				+ '&context_id=' + encodeURIComponent(context_id) 
				+ '&image_width=' + encodeURIComponent(image_width) 
				+ '&image_height=' + encodeURIComponent(image_height) 
				;
			
			if($editor_button.attr('data-create-defaults'))
				popup_url += '&defaults=' + encodeURIComponent($editor_button.attr('data-create-defaults'));
			
			var $editor_popup = genericAjaxPopup('avatar_editor', popup_url, null, false, '650');
			
			// Set the default image/url in the chooser
			var evt = new jQuery.Event('cerb-avatar-set-defaults');
			evt.avatar = {
				'imagedata': $avatar_image.attr('src')
			};
			$editor_popup.trigger(evt);
			
			$editor_popup.one('avatar-editor-save', function(e) {
				genericAjaxPopupClose('avatar_editor');
				
				if(undefined == e.avatar || undefined == e.avatar.imagedata)
					return;
				
				if(e.avatar.empty) {
					$avatar_image.attr('src', e.avatar.imagedata);
					$avatar_chooser.next('input:hidden').val('data:null');
				} else {
					$avatar_image.attr('src', e.avatar.imagedata);
					$avatar_chooser.next('input:hidden').val(e.avatar.imagedata);
				}
				
			});
		});
	}
}

var ajax = new cAjaxCalls();

(function ($) {
	
	// Abstract property grid
	
	$.fn.cerbPropertyGrid = function(options) {
		return this.each(function() {
			var $grid = $(this);
			var $properties = $grid.find('> div');
			
			var column_width = parseInt($grid.attr('data-column-width'));

			if(0 === column_width)
				column_width = 100;
			
			$properties.each(function() {
				var $div = $(this);
				var width = $div.width();
				// Round widths to even increments (e.g. auto-span)
				$div.width(Math.ceil(width/column_width)*column_width);
			});
		});
	}

	$.fn.cerbToolbar = function(options) {
		if('object' !== typeof options)
			options = {};

		if(!options.hasOwnProperty('caller') || 'object' !== typeof options.caller) {
			options.caller = {
				id: '',
				params: { }
			};
		}

		if(!options.hasOwnProperty('mode') || 'string' !== typeof options.mode) {
			options.mode = 'popup';
		}

		if(!options.hasOwnProperty('target')) {
			options.target = null;
		}

		if(!options.hasOwnProperty('width') || null == options.width) {
			options.width = '50%';
		}

		if(!options.hasOwnProperty('start') || 'function' !== typeof options.start) {
			options.start = function() {};
		}

		if(!options.hasOwnProperty('done') || 'function' !== typeof options.done) {
			options.done = function() {};
		}

		if(!options.hasOwnProperty('reset') || 'function' !== typeof options.reset) {
			options.reset = function() {};
		}

		if(!options.hasOwnProperty('error') || 'function' !== typeof options.error) {
			options.error = function() {};
		}

		if(!options.hasOwnProperty('interaction_class') || 'string' !== typeof options.interaction_class) {
			options.interaction_class = 'cerb-bot-trigger';
		}

		return this.each(function() {
			var $toolbar = $(this);

			$toolbar.on('cerb-toolbar--refreshed', function() {
				// Interactions
				$toolbar
					.find('.' + options.interaction_class)
					.cerbBotTrigger({
						'caller': options.caller,
						'mode': options.mode,
						'target': options.target,
						'width': options.width,
						'start': options.start,
						'reset': options.reset,
						'done': options.done,
						'error': options.error
					})
					.on('click', function(e) {
						$(this).closest('.ui-menu').hide();
					})
				;

				// Menus
				$toolbar
					.find('button[data-cerb-toolbar-menu]')
					.each(function() {
						var $button = $(this);
						var $ul = $button.next('ul');

						$button.on('click', function(e) {
							e.stopPropagation();
							if(!$ul.is(':visible')) {
								$ul.toggle().position({
									my: 'left top',
									at: 'left bottom',
									of: $button,
									collision: 'fit'
								});

								// Focus the first item
								$ul.menu('focus', null, $ul.find('.ui-menu-item:first')).focus();
							} else {
								$ul.hide();
							}
						});
						
						if($button.attr('data-cerb-toolbar-menu-hover') !== undefined) {
							$button
								.hoverIntent({
									interval: 200,
									over: function () {
										$ul.show().position({
											my: 'left top',
											at: 'left bottom',
											of: $button,
											collision: 'fit'
										});
										
										// Focus the first item
										$ul.menu('focus', null, $ul.find('.ui-menu-item:first')).focus();
									},
									out: function () {
									}
								})
							;
						}
					})
					.next('ul.cerb-float')
					.hoverIntent({
						timeout: 0,
						over: function() {},
						out: function() {
							$(this).hide();
						}
					})
					.menu({
						select: function(event, ui) {
							event.stopPropagation();
							var $li = $(ui.item);
							if($li.is('.' + options.interaction_class))
								$li.click();
						}
					})
				;
			});

			$toolbar.triggerHandler('cerb-toolbar--refreshed');
		});
	}

	$.fn.cerbCodeEditor = function(options) {
		var langTools = ace.require("ace/ext/language_tools");
		
		return this.each(function(iteration) {
			var $this = $(this);
			
			if(!$this.is('textarea, :text'))
				return;
			
			var mode = $this.attr('data-editor-mode');
			var maxLines = $this.attr('data-editor-lines') || 20;
			var showGutter = $this.attr('data-editor-gutter');
			var showLineNumbers = $this.attr('data-editor-line-numbers');
			var isReadOnly = $this.attr('data-editor-readonly');
			var withTwigAutocompletion = $this.hasClass('placeholders');
			
			var aceOptions = {
				showLineNumbers: 'false' == showLineNumbers ? false : true,
				showGutter: 'false' == showGutter ? false : true,
				showPrintMargin: false,
				wrap: true,
				enableBasicAutocompletion: [],
				enableSnippets: false,
				tabSize: 2,
				useSoftTabs: false,
				minLines: 2,
				maxLines: maxLines,
				readOnly: 'true' == isReadOnly ? true : false,
				useWorker: false
			};
			
			if(null == mode)
				mode = 'ace/mode/twig';
			
			$this
				.removeClass('placeholders')
				.hide()
				;
			
			var editor_id = Devblocks.uniqueId('editor');
			
			var $editor = $('<pre/>')
				.attr('id', editor_id)
				.css('margin', '0')
				.css('position', 'relative')
				.css('width', '100%')
				.insertAfter($this)
				;
			
			if(withTwigAutocompletion)
				$editor.addClass('placeholders');
			
			var editor = ace.edit(editor_id, {
				useWorker: false
			});
			editor.$blockScrolling = Infinity;
			editor.setTheme("ace/theme/cerb-2022011201");
			editor.session.setMode(mode);
			editor.session.setValue($this.val());

			editor.session.setOption('indentedSoftWrap', false);
			editor.setOption('wrap', true);

			editor.commands.addCommand({
				name: 'ResizeMaxLinesIncrease',
				bindKey: { win: "Ctrl-Shift-Down", mac: "Ctrl-Shift-Down" },
				exec: function() {
					let maxLines = editor.getOption('maxLines') || localStorage.cerbCodeEditorMaxLines || 20;
					localStorage.cerbCodeEditorMaxLines = ++maxLines;
					editor.setOption('maxLines', localStorage.cerbCodeEditorMaxLines);
				}
			});
			editor.commands.addCommand({
				name: 'ResizeMaxLinesDecrease',
				bindKey: { win: "Ctrl-Shift-Up", mac: "Ctrl-Shift-Up" },
				exec: function() {
					let maxLines = editor.getOption('maxLines') || localStorage.cerbCodeEditorMaxLines || 20;
					localStorage.cerbCodeEditorMaxLines = --maxLines;
					editor.setOption('maxLines', localStorage.cerbCodeEditorMaxLines);
				}
			});
			
			$this
				.data('$editor', $editor)
				.data('editor', editor)
				;
			
			langTools.setCompleters([]);
			
			$editor.on('cerb.update', function(e) {
				$this.val(editor.session.getValue());
			});
			
			$editor.on('cerb.insertAtCursor', function(e) {
				if(e.replace)
					editor.session.setValue('');

				editor.insertSnippet(e.content);

				var cursor_pos = editor.getCursorPosition();
				cursor_pos.row += 5;
				editor.renderer.scrollCursorIntoView(cursor_pos);

				editor.focus();
			});

			$editor.on('cerb.appendText', function(e) {
				var value = editor.getValue();

				if(value.length > 0 && value.substr(-1) !== "\n")
					editor.setValue(value + "\n\n");

				editor.navigateFileEnd();
				editor.insertSnippet(e.content);

				var cursor_pos = editor.getCursorPosition();
				cursor_pos.row += 5;
				editor.renderer.scrollCursorIntoView(cursor_pos);

				editor.focus();
			});

			editor.session.on('change', function() {
				$editor.trigger('cerb.update');
			});
			
			if(withTwigAutocompletion) {
				var autocompleterTwig = {
					insertMatch: function(editor, data) {
						data.completer = null;
						editor.completer.insertMatch(data);
					},
					getCompletions: function(editor, session, pos, prefix, callback) {
						editor.completer.autoSelect = false;

						var token = session.getTokenAt(pos.row, pos.column);
						
						if(null == token)
							return;
						
						// This should only happen for the Twig editor (not embeds)
						if('ace/mode/twig' == session.getMode().$id) {
							if(token == null) {
								callback(null, twigAutocompleteSuggestions.snippets.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
						}
						
						if(token.type == 'identifier' || (token.type == 'text' && token.start > 0)) {
							var prevToken = session.getTokenAt(pos.row, token.start);
							
							if(prevToken && prevToken.type == 'meta.tag.twig') {
								callback(null, twigAutocompleteSuggestions.tags.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.twig') {
								callback(null, twigAutocompleteSuggestions.functions.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
							
							if(prevToken && prevToken.type == 'keyword.operator.other' && prevToken.value == '|') {
								callback(null, twigAutocompleteSuggestions.filters.map(function(c) {
									c.score = 5000;
									c.completer = autocompleterTwig;
									return c;
								}));
								return;
							}
						}
						
						if(token.type == 'meta.tag.twig') {
							var results = [].concat(twigAutocompleteSuggestions.tags).concat(twigAutocompleteSuggestions.functions);
							callback(null, results.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						if(token.type == 'keyword.operator.other' && token.value == '|') {
							callback(null, twigAutocompleteSuggestions.filters.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						if(token.type == 'variable.other.readwrite.local.twig' && token.value == '{{') {
							callback(null, twigAutocompleteSuggestions.functions.map(function(c) {
								c.score = 5000;
								c.completer = autocompleterTwig;
								return c;
							}));
							return;
						}
						
						callback(false);
					}
				};
				
				aceOptions.enableBasicAutocompletion.push(autocompleterTwig);
			}
			
			if(mode === 'ace/mode/cerb_query') {
				aceOptions.useSoftTabs = true;
				
			} else if(mode === 'ace/mode/yaml') {
				aceOptions.useSoftTabs = true;

			} else if(mode === 'ace/mode/cerb_kata') {
				aceOptions.useSoftTabs = true;
				aceOptions.enableLinking = true;
				
				if(navigator.userAgent.toLowerCase().indexOf('iphone') >= 0) {
					aceOptions.wrap = true;
				} else {
					aceOptions.wrap = false;
				}

				// editor.on('linkHover', function(data) {
				// });

				editor.on('linkClick', function(data) {
					if(data.token.type !== 'text.cerb-uri')
						return false;

					var uri_parts = data.token.value.split(':');

					if(3 !== uri_parts.length)
						return false;

					if('cerb' !== uri_parts[0])
						return false;

					// Open card popup https://cerb.ai/
					$('<div/>')
						.attr('data-context', uri_parts[1])
						.attr('data-context-id', uri_parts[2])
						.cerbPeekTrigger()
						.on('cerb-peek-saved cerb-peek-deleted cerb-peek-closed', function() {
							$(this).remove();
						})
						.click()
					;

					editor.exitMultiSelectMode();
				});
			}
			
			editor.setOptions(aceOptions);
		});
	};
	
	$.fn.cerbCodeEditorToolbarEventHandler = function(options) {
		if(undefined === options)
			options = {};

		if(!options.hasOwnProperty('editor') || 'object' !== typeof options.editor) {
			options.editor = null;
		}

		return this.each(function() {
			var $toolbar = $(this);
			var $fieldset = $toolbar.closest('fieldset');

			var $panel_help = $fieldset.find('[data-cerb-event-placeholders]');
			var $panel_tester = $fieldset.find('[data-cerb-event-tester]');
			
			$toolbar.find('.cerb-editor-button-event-placeholders').on('click', function() {
			  var $button = $(this);
			
			  if($panel_help.is(':visible')) {
				  $button.removeClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_help.hide();
			
			  } else {
				  $button.addClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_help.fadeIn();
			  }
			});
			
			var $editor_placeholders = $panel_tester.find('[data-editor-mode]')
			  .cerbCodeEditor()
			  .next('pre.ace_editor')
			;
			
			var editor_placeholders = ace.edit($editor_placeholders.attr('id'));
			
			$toolbar.find('.cerb-editor-button-event-tester').on('click', function() {
			  var $button = $(this);
			
			  if($panel_tester.is(':visible')) {
				  $button.removeClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_tester.hide();
			
			  } else {
				  $button.addClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_tester.fadeIn();
			  }
			});
			
			$panel_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function() {
				if(!options.editor)
					return;
				
				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation_event');
				formData.set('action', 'tester');

				formData.set('automations_kata', options.editor.getValue());
				formData.set('placeholders_kata', editor_placeholders.getValue());

				var $results = $panel_tester.find('[data-cerb-event-tester-results]').empty();

				genericAjaxPost(formData, null, null, function(json) {
					if(typeof json !== 'object')
						return;

					var $div;

					if(json.hasOwnProperty('error') && json.error) {
						$div = $('<div/>')
							.addClass('ui-state-error ui-corner-all')
							.css('padding', '5px')
							.text(json.error)
						;
						$results.append($div);
						return;
					}

					if(Array.isArray(json)) {
						var $container = $('<div/>')
							.addClass('bubbles')
						;

						for(var i in json) {
							var handler = json[i];

							$div = $('<div/>')
								.addClass('bubble')
								.css('font-weight', 'bold')
								.css('margin', '0 5px 5px 0')
								.css('cursor', 'pointer')
								.attr('data-line', handler.hasOwnProperty('kata') && handler.kata.line ? handler.kata.line : null)
								.text(handler.id)
								.on('click', function() {
									var line = $(this).attr('data-line');

									if(null === line)
										return;

									// Move to the definition
									options.editor.gotoLine(line, 0, true);
									options.editor.focus();
								})
								.appendTo($container)
							;
						}

						var $close_button = $('<span/>')
							.addClass('glyphicons glyphicons-circle-remove')
							.css('position', 'absolute')
							.css('top', '0')
							.css('right', '0')
							.css('cursor', 'pointer')
							.css('font-size', '16px')
							.on('click', function() {
								$results.empty();
							})
						;

						var $fieldset = $('<fieldset/>').addClass('black');
						$fieldset.append($('<legend/>').text('Results'));
						$fieldset.append($container);

						$results.append($fieldset);
						$results.append($close_button);
					}
				});				
			});
		});
	};
	
	$.fn.cerbCodeEditorToolbarHandler = function(options) {
		if(undefined === options)
			options = {};

		if(!options.hasOwnProperty('editor') || 'object' !== typeof options.editor) {
			options.editor = null;
		}

		return this.each(function() {
			var $toolbar = $(this);
			var $fieldset = $toolbar.closest('fieldset');

			var $panel_help = $fieldset.find('[data-cerb-toolbar-help]'); 
			var $panel_tester = $fieldset.find('[data-cerb-toolbar-tester]');
			
			$toolbar.find('.cerb-editor-button-toolbar-help').on('click', function() {
			  var $button = $(this);
			
			  if($panel_help.is(':visible')) {
				  $button.removeClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_help.hide();
			
			  } else {
				  $button.addClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_help.fadeIn();
			  }
			});
			
			var $editor_placeholders = $panel_tester.find('[data-editor-mode]')
			  .cerbCodeEditor()
			  .next('pre.ace_editor')
			;
			
			var editor_placeholders = ace.edit($editor_placeholders.attr('id'));
			
			$toolbar.find('.cerb-editor-button-toolbar-tester').on('click', function() {
			  var $button = $(this);
			
			  if($panel_tester.is(':visible')) {
				  $button.removeClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_tester.hide();
			
			  } else {
				  $button.addClass('cerb-code-editor-toolbar-button--enabled');
				  $panel_tester.fadeIn();
			  }
			});
			
			$panel_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function() {
				if(!options.editor)
					return;
				
				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'toolbar');
				formData.set('action', 'tester');

				formData.set('toolbar_kata', options.editor.getValue());
				formData.set('placeholders_kata', editor_placeholders.getValue());

				var $results = $panel_tester.find('[data-cerb-toolbar-tester-results]').empty();

				genericAjaxPost(formData, null, null, function(json) {
					if(typeof json !== 'object')
						return;

					var $div;

					if(json.hasOwnProperty('error') && json.error) {
						$div = $('<div/>')
							.addClass('ui-state-error ui-corner-all')
							.css('padding', '5px')
							.text(json.error)
						;
						$results.append($div);
						return;
					} else if (json.hasOwnProperty('html') && json.html) {
						
					}

					$results.html($(json.html));
				});
			});
		});
	};

	$.fn.cerbCodeEditorToolbarHtml = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);

	      var $pre = $editor_toolbar.nextAll('pre.ace_editor');

	      var editor = ace.edit($pre.attr('id'));

          // Bold
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--bold').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '<b>' + selected_text + '</b>');
              editor.focus();
          });

          // Italics
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--italic').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '<i>' + selected_text + '</i>');
              editor.focus();
          });

		  // Headings
		  $editor_toolbar.find('.cerb-html-editor-toolbar-button--heading').on('click', function () {
			  var selected_text = editor.getSelectedText();

			  if (0 === selected_text.length)
				  return;

			  editor.session.replace(editor.getSelectionRange(), '<h1>' + selected_text + '</h1>');
			  editor.focus();
		  });

		  // Link
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--link').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
				  editor.insertSnippet('<a href="${1:https://example.com}">${2:link text}</a>');
				  editor.focus();
				  return;
			  }

			  editor.session.replace(editor.getSelectionRange(), '');
              editor.insertSnippet('<a href="${1:https://example.com}">' + selected_text + '</a>');
			  editor.focus();
          });

          // List
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--list').on('click', function () {
              var range = editor.getSelectionRange();

              // [TODO]

              editor.session.indentRows(range.start.row, range.end.row, '* ');
              editor.focus();
          });

          // Image
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
                  var file_id = event.values[0];
                  var file_label = event.labels[0];
                  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

                  var url =
                      document.location.protocol
                      + '//'
                      + document.location.host
                      + DevblocksWebPath
                      + 'files/'
                      + encodeURIComponent(file_id) + '/'
                      + encodeURIComponent(file_name)
                  ;

                  $editor_toolbar.triggerHandler(
                      $.Event(
                          'cerb-editor-toolbar-image-inserted',
                          { labels: event.labels, values: event.values, url: url }
                      )
                  );
              });
          });

          // Quote
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--quote').on('click', function () {
			  var selected_text = editor.getSelectedText();

			  if (0 === selected_text.length)
				  return;

			  editor.session.replace(editor.getSelectionRange(), '<blockquote>' + selected_text + '</blockquote>');
			  editor.focus();
          });

          // Code
          $editor_toolbar.find('.cerb-html-editor-toolbar-button--code').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
	              editor.insertSnippet("<pre><code>\n${1:your code goes here}\n</code></pre>\n");
                  editor.focus();
                  return;
              }

              var range = editor.getSelectionRange();

              // If multiple lines, use block format. Otherwise use backticks on vars
              if (range.start.row !== range.end.row) {
                  range.start.column = 0;
                  range.end.row++;
                  range.end.column = 0;
                  editor.selection.setRange(range);
                  editor.session.replace(range, "<pre><code>\n" + editor.getSelectedText() + "</code></pre>\n");

              } else {
                  editor.session.replace(editor.getSelectionRange(), '<var>' + selected_text + '</var>');
              }

              editor.focus();
          });

          // Table
		  $editor_toolbar.find('.cerb-html-editor-toolbar-button--table').on('click', function () {
			editor.insertSnippet('<table>\n<tr>\n<th>Column 1</th>\n<th>Column 2</th>\n</tr>\n<tr>\n<td>Cell 1</td>\n<td>Cell 2</td>\n</tr>\n</table>\n');
		  	editor.focus();
          });
      });
    };

	$.widget('cerb.cerbTextEditor', {
		options: {

		},

		_create: function() {
			this.editor = this.element[0];

			this.element
				.css('width', '100%')
				.css('height', '20em')
			;
		},

		getCursorPosition: function() {
			return this.editor.selectionEnd;
		},

		setCursorPosition: function(index) {
			this.editor.selectionStart = index;
			this.editor.selectionEnd = index;
		},

		getCurrentWordPos: function() {
			var start = this.editor.selectionStart-1;
			var end = this.editor.selectionStart;

			for(var x = start; x >= 0; x--) {
				var char = this.editor.value[x];

				if(char.match(/\s/)) {
					start = x + 1;
					break;
				}

				if(0 === x) {
					start = x;
				}
			}

			return {
				start: start,
				end: end
			};
		},

		getCurrentLinePos: function() {
			var start = this.editor.selectionStart-1;
			var end = this.editor.selectionStart;

			for(var x = start; x >= 0; x--) {
				var char = this.editor.value[x];

				if(char.match(/[\r\n^]/)) {
					start = x + 1;
					break;
				}

				if(0 === x) {
					start = x;
				}
			}

			return {
				start: start,
				end: end
			};
		},

		getCurrentLine: function() {
			var pos = this.getCurrentLinePos();
			return this.editor.value.substring(pos.start,pos.end);
		},

		getCurrentWord: function() {
			var pos = this.getCurrentWordPos();
			return this.editor.value.substring(pos.start,pos.end);
		},

		selectCurrentWord: function() {
			var pos = this.getCurrentWordPos();
			this.setSelection(pos.start, pos.end);
		},

		selectCurrentLine: function() {
			var pos = this.getCurrentLinePos();
			this.setSelection(pos.start, pos.end);
		},

		replaceCurrentWord: function(replaceWith) {
			this.selectCurrentWord();
			this.replaceSelection(replaceWith);
		},

		replaceCurrentLine: function(replaceWith) {
			this.selectCurrentLine();
			this.replaceSelection(replaceWith);
		},

		getSelectionBounds: function() {
			return {
				start: this.editor.selectionStart,
				end: this.editor.selectionEnd
			};
		},

		getSelection: function() {
			var bounds = this.getSelectionBounds();
			return this.editor.value.substring(bounds.start,bounds.end);
		},

		setSelection: function(start, end) {
			this.editor.selectionStart = start;
			this.editor.selectionEnd = end;
		},

		insertText: function(insertText) {
			var start = this.getCursorPosition();

			// Normalize line endings
			insertText = insertText.replace(/\r/g, '');

			var newValue =
				this.editor.value.substring(0,start)
				+ insertText
				+ this.editor.value.substring(start)
			;

			var oldLength = this.editor.value.length;
			this.editor.value = newValue;

			var offset = newValue.length - oldLength;

			this.setSelection(start + offset, start + offset);

			this.editor.blur();
			this.editor.focus();
		},

		replaceSelection: function(replaceWith) {
			var bounds = this.getSelectionBounds();

			// Normalize line endings
			replaceWith = replaceWith.replace(/\r/g, '');

			var newValue =
				this.editor.value.substring(0,bounds.start)
				+ replaceWith
				+ this.editor.value.substring(bounds.end)
			;

			var offset = newValue.length - this.editor.value.length;

			this.editor.value = newValue;

			this.setSelection(bounds.end + offset, bounds.end + offset);

			this.editor.blur();
			this.editor.focus();
		},

		prefixCurrentLine: function(prefixWith) {
			var start = Math.max(0, this.editor.value.substring(0, this.editor.selectionStart).lastIndexOf('\n')+1);
			this.editor.selectionStart = start;
			this.prefixSelection(prefixWith);
		},

		prefixSelection: function(prefixWith) {
			var selectedText = this.getSelection();
			this.replaceSelection(prefixWith + selectedText);
		},

		wrapSelection: function(wrapWith) {
			var selectedText = this.getSelection();
			this.replaceSelection(wrapWith + selectedText + wrapWith);
		}
	});

	$.fn.cerbTextEditorToolbarMarkdown = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);
	      var $editor = $editor_toolbar.nextAll('textarea');

	      if(0 === $editor.length)
	      	return;

          // Bold
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--bold').on('click', function () {
          	$editor.cerbTextEditor('wrapSelection', '**');
          });

          // Italics
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--italic').on('click', function () {
          	$editor.cerbTextEditor('wrapSelection', '_');
          });

          // Headings
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--heading').on('click', function () {
          	$editor.cerbTextEditor('prefixSelection', '# ');
          });

          // Link
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--link').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

              if (0 === selectedText.length) {
				$editor.cerbTextEditor('insertText', '[link text](https://example.com)');
				return;
			  }

              var bounds = $editor.cerbTextEditor('getSelectionBounds');
              var cursor_at = bounds.start + selectedText.length + 3;
              var default_link = 'https://example.com';

              $editor.cerbTextEditor('replaceSelection', '[' + selectedText + '](' + default_link + ')');
              $editor.cerbTextEditor('setSelection', cursor_at, cursor_at + default_link.length);
		  });

          // Image
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
				  var file_id = event.values[0];
				  var file_label = event.labels[0];
				  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

				  var url =
					  document.location.protocol
					  + '//'
					  + document.location.host
					  + DevblocksWebPath
					  + 'files/'
					  + encodeURIComponent(file_id) + '/'
					  + encodeURIComponent(file_name)
				  ;

				  $editor_toolbar.triggerHandler(
					  $.Event(
						  'cerb-editor-toolbar-image-inserted',
						  { labels: event.labels, values: event.values, file_id: file_id, file_name: file_name, url: url }
					  )
				  );
              });
          });

		  // List
		  $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--list').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

			  if (0 === selectedText.length) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '* ');
			    return;
			  }

			  if(-1 === selectedText.indexOf("\n")) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '* ');
			  } else {
			  	var quotedText = $.trim(selectedText).replace(new RegExp('\n', 'g'),'\n* ');
			  	$editor.cerbTextEditor('replaceSelection', '* ' + quotedText + '\n');
			  }
		  });

		  // Quote
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--quote').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

			  if (0 === selectedText.length) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '> ');
			    return;
			  }

			  if(-1 === selectedText.indexOf("\n")) {
			  	$editor.cerbTextEditor('prefixCurrentLine', '> ');
			  } else {
			  	var quotedText = $.trim(selectedText).replace(new RegExp('\n', 'g'),'\n> ');
			  	$editor.cerbTextEditor('replaceSelection', '> ' + quotedText + '\n');
			  }
          });

          // Code
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--code').on('click', function () {
			  var selectedText = $editor.cerbTextEditor('getSelection');

              if (0 === selectedText.length) {
              	  $editor.cerbTextEditor('insertText', "~~~\nyour code goes here\n~~~\n");
                  return;
              }

              if(-1 === selectedText.indexOf("\n")) {
              	 $editor.cerbTextEditor('wrapSelection', '`');
			  } else {
              	 $editor.cerbTextEditor('wrapSelection', '~~~\n');
			  }
          });

          // Table
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--table').on('click', function () {
          	  $editor.cerbTextEditor('insertText', "Column | Column\n--- | ---\nValue | Value\n");
          });

          // Keyboard shortcuts
		  $editor.bind('keydown', 'ctrl+space', function(e) {
		  	  if(e.metaKey)
		  	  	return;

			  e.preventDefault();
			  e.stopPropagation();
			  $editor.autocomplete('search');
		  });

          $editor_toolbar.find('.cerb-code-editor-toolbar-button').each(function() {
          	var $button = $(this);
          	var shortcut = $button.attr('data-cerb-key-binding');

          	if(!shortcut)
          		return;

          	$editor.bind('keydown', shortcut, function(e) {
			  e.preventDefault();
			  e.stopPropagation();
			  $button.click();
		 	});
		  });
      });
    };

	$.fn.cerbCodeEditorToolbarMarkdown = function() {
	  return this.each(function() {
	      var $editor_toolbar = $(this);

	      var $pre = $editor_toolbar.nextAll('pre.ace_editor');

	      var editor = ace.edit($pre.attr('id'));

          // Bold
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--bold').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '**' + selected_text + '**');
              editor.focus();
          });

          // Italics
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--italic').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length)
                  return;

              editor.session.replace(editor.getSelectionRange(), '_' + selected_text + '_');
              editor.focus();
          });

          // Headings
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--heading').on('click', function () {
			  var range = editor.getSelectionRange();

			  editor.session.indentRows(range.start.row, range.end.row, '#');
			  editor.focus();
          });

          // Link
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--link').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
			  editor.insertSnippet('[${1:link text}](${2:https://example.com})');
				  editor.focus();
				  return;
			  }

			  editor.session.replace(editor.getSelectionRange(), '');
			  editor.insertSnippet('[' + selected_text + '](${1:https://example.com})');
			  editor.focus();
          });

          // List
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--list').on('click', function () {
              var range = editor.getSelectionRange();

              editor.session.indentRows(range.start.row, range.end.row, '* ');
              editor.focus();
          });

          // Image
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--image').on('click', function () {
              var $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=1', null, true, '750');

              $chooser.one('chooser_save', function (event) {
				  var file_id = event.values[0];
				  var file_label = event.labels[0];
				  var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));

				  var url =
					  document.location.protocol
					  + '//'
					  + document.location.host
					  + DevblocksWebPath
					  + 'files/'
					  + encodeURIComponent(file_id) + '/'
					  + encodeURIComponent(file_name)
				  ;

				  $editor_toolbar.triggerHandler(
					  $.Event(
						  'cerb-editor-toolbar-image-inserted',
						  { labels: event.labels, values: event.values, file_id: file_id, file_name: file_name, url: url }
					  )
				  );
              });
          });

          // Quote
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--quote').on('click', function () {
              var range = editor.getSelectionRange();

              editor.session.indentRows(range.start.row, range.end.row, '> ');
              editor.focus();
          });

          // Code
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--code').on('click', function () {
              var selected_text = editor.getSelectedText();

              if (0 === selected_text.length) {
              editor.insertSnippet("~~~\n${1:your code goes here}\n~~~\n");
                  editor.focus();
                  return;
              }

              var range = editor.getSelectionRange();

              // If multiple lines, use block format. Otherwise use backticks on vars
              if (range.start.row != range.end.row) {
                  range.start.column = 0;
                  range.end.row++;
                  range.end.column = 0;
                  editor.selection.setRange(range);
                  editor.session.replace(range, "~~~\n" + editor.getSelectedText() + "~~~~\n");

              } else {
                  editor.session.replace(editor.getSelectionRange(), '`' + selected_text + '`');
              }

              editor.focus();
          });

          // Table
          $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--table').on('click', function () {
              editor.insertSnippet("Column | Column\n--- | ---\nValue | Value\n");
              editor.focus();
          });
      });
    };

	$.fn.cerbCodeEditorAutocompleteKata = function(autocomplete_options) {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();

			if(!e.editor.completer) {
				var Autocomplete = require('ace/autocomplete').Autocomplete;
				e.editor.completer = new Autocomplete();
			}

			if('insertstring' === e.command.name) {
				if(!e.editor.completer.activated || e.editor.completer.isDynamic) {
					if(1 === e.args.length) {
						e.editor.completer.showPopup(e.editor);
					}
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}

			editor.completer.autocomplete_suggestions = {};
			
			if(autocomplete_options.autocomplete_suggestions)
				editor.completer.autocomplete_suggestions = autocomplete_options.autocomplete_suggestions;
			
			var autocompleterKata = {
				identifierRegexps: [
					/[a-zA-Z_0-9\*\#\@\.\$\:\-\u00A2-\uFFFF]/
				],
				formatSuggestions: function(suggestions) {
					return suggestions.map(function(data) {
						if('object' == typeof data) {
							if(
								!data.hasOwnProperty('docHTML') 
								&& data.hasOwnProperty('caption')
								&& data.hasOwnProperty('description')
							) {
								var $help = $('<div/>')
									.append($('<b/>').text(data.caption))
									.append($('<br>'))
									.append($('<span/>').text(data.description))
								;
								data.docHTML = $help.html();
							}
							
							if(!data.hasOwnProperty('score'))
								data.score = 1000;

							data.completer = {
								insertMatch: autocompleterKata.insertMatch
							};
							
							return data;

						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000,
								completer: {
									insertMatch: autocompleterKata.insertMatch
								}
							};
						}
					});
				},
				insertMatch: function(editor, data) {
					delete data.completer;
					
					// Run the callback, if false then do the default
					if(
						autocomplete_options.hasOwnProperty('onSelect') 
						&& 'function' === typeof autocomplete_options.onSelect) 
					{
						var completions = editor.completer.completions;
						var callback = function(result) {
							
							if('string' === typeof result) {
								data.snippet = result;
							}
							
							editor.completer.completions = completions;
							editor.completer.insertMatch(data);
						};
						
						autocomplete_options.onSelect(data, editor, callback);

					} else if(data.hasOwnProperty('interaction')) {
						let completions = editor.completer.completions;

						// Remove the filter text when starting the interaction
						if(completions.filterText) {
							var ranges = editor.selection.getAllRanges();
							for (var i = 0, range; range = ranges[i]; i++) {
								range.start.column -= completions.filterText.length;
								editor.session.remove(range);
							}
						}

						editor.endOperation();

						var $interaction =
							$('<div/>')
								.attr('data-interaction-uri', data['interaction'])
								.attr('data-interaction-params', data['interaction_params'] || '')
								.cerbBotTrigger({
									'caller': 'automation.editor.kata.autocomplete',
									'start': function(formData) {
										//formData.set('caller[params][draft_id]', draft_id);
									},
									'done': function(e) {
										e.stopPropagation();
										$interaction.remove();
										Devblocks.interactionWorkerPostActions(e.eventData, editor);
									},
									'error': function(e) {
										e.stopPropagation();
										$interaction.remove();
									},
									'abort': function(e) {
										e.stopPropagation();
										$interaction.remove();
									}
								})
								.click()
						
					} else {
						editor.completer.insertMatch(data);
					}
				},
				formatData: function(scope_key) {
					return this.formatSuggestions(editor.completer.autocomplete_suggestions[scope_key]);
				},
				parseCompletions: function(callback, editor, scope_key, prefix) {
					var completions = editor.completer.autocomplete_suggestions[scope_key];

					editor.completer.isDynamic = false;
					
					if(Array.isArray(completions)) {
						return callback(null, autocompleterKata.formatSuggestions(completions));
						
					} else if(typeof completions == 'object') {
						editor.completer.isDynamic = true;
						
						if(completions.hasOwnProperty('type')) {
							var formData = null;
							
							if('cerb-uri' === completions['type']) {
								editor.completer.getPopup().container.style.width = '500px';
								
								var params = {};
								
								if(completions.hasOwnProperty('params')) {
									params = completions.params;
								}
								
								if(
									autocomplete_options.hasOwnProperty('autocomplete_type_defaults')
									&& autocomplete_options.autocomplete_type_defaults.hasOwnProperty('cerb-uri')
								) {
									params = Object.assign(autocomplete_options.autocomplete_type_defaults['cerb-uri'], params);
								}
								
								formData = new FormData();
								formData.set('c', 'ui');
								formData.set('a', 'kataSuggestionsCerbUriJson');
								formData.set('prefix', prefix);
								
								if(params) {
									formData.set('params', $.param(params));
								}
								
							} else if('record-field' === completions['type']) {
								editor.completer.getPopup().container.style.width = '500px';
								
								formData = new FormData();
								formData.set('c', 'ui');
								formData.set('a', 'kataSuggestionsRecordFieldJson');
								formData.set('prefix', prefix);

								if(completions.hasOwnProperty('params')) {
									if(completions.params.hasOwnProperty('record_type') && 'string' === typeof completions.params.record_type)
										formData.set('params[record_type]', completions.params.record_type);
									if(completions.params.hasOwnProperty('field_key') && 'string' === typeof completions.params.field_key)
										formData.set('params[field_key]', completions.params.field_key);
								}
								
							} else if('record-fields' === completions['type']) {
								editor.completer.getPopup().container.style.width = '400px';

								var record_type_path = Devblocks.cerbCodeEditor.getKataTokenPath(
									null,
									editor
								);

								record_type_path.pop();
								record_type_path.push('record_type:');
								
								var key_row = Devblocks.cerbCodeEditor.getKataRowByPath(editor, record_type_path.join(''));
								var key_line = editor.session.getLine(key_row);
								var matches = key_line.match(/[^:]*:\s*(.*)/i);

								if(Array.isArray(matches) && 2 == matches.length) {
									formData = new FormData();
									formData.set('c', 'ui');
									formData.set('a', 'kataSuggestionsRecordFieldsJson');
									formData.set('prefix', prefix);
									formData.set('params[record_type]', matches[1]);
								}
								
							} else if('record-type' === completions['type']) {
								editor.completer.getPopup().container.style.width = '300px';
								editor.completer.isDynamic = false;

								formData = new FormData();
								formData.set('c', 'ui');
								formData.set('a', 'kataSuggestionsRecordTypeJson');
								formData.set('prefix', prefix);
								
							} else if('icon' === completions['type']) {
								editor.completer.getPopup().container.style.width = '200px';
								
								formData = new FormData();
								formData.set('c', 'ui');
								formData.set('a', 'kataSuggestionsIconJson');
								formData.set('prefix', prefix);
								
							} else if('automation-inputs' === completions['type']) {
								editor.completer.getPopup().container.style.width = '300px';
								
								var uri_path = Devblocks.cerbCodeEditor.getKataTokenPath(
									null,
									editor
								);
								
								uri_path.pop();
								uri_path.push('uri:');
								
								var key_row = Devblocks.cerbCodeEditor.getKataRowByPath(editor, uri_path.join(''));
								var key_line = editor.session.getLine(key_row);
								var matches = key_line.match(/[^:]*:\s*(.*)/i);
								
								if(Array.isArray(matches) && 2 === matches.length) {
									formData = new FormData();
									formData.set('c', 'ui');
									formData.set('a', 'kataSuggestionsAutomationInputsJson');
									formData.set('prefix', prefix);
									formData.set('params[uri]', matches[1]);
								}

							} else if('automation-command-params' === completions['type']) {
								editor.completer.getPopup().container.style.width = '400px';

								let key_path = Devblocks.cerbCodeEditor.getKataTokenPath(
									null,
									editor
								);

								let inputs_path = key_path.slice();

								// We can be nested, so find the closest parent
								while(inputs_path.length && 'inputs:' !== inputs_path.at(-1)) {
									inputs_path.pop();
								}

								if('inputs:' === inputs_path.at(-1)) {
									let name_path = inputs_path.slice();
									name_path.push('name:');

									let key_row = Devblocks.cerbCodeEditor.getKataRowByPath(editor, name_path.join(''));
									let key_line = editor.session.getLine(key_row);
									let matches = key_line.match(/[^:]*:\s*(.*)/i);
									let rel_path = key_path.slice(inputs_path.length + 1);

									if(Array.isArray(matches) && 2 === matches.length) {
										formData = new FormData();
										formData.set('c', 'ui');
										formData.set('a', 'kataSuggestionsAutomationCommandParamsJson');
										formData.set('prefix', prefix);
										formData.set('params[name]', matches[1]);
										formData.set('params[prefix]', prefix);
										formData.set('params[key_path]', rel_path.join(''));
										formData.set('params[key_fullpath]', key_path.join(''));
										formData.set('params[script]', editor.getValue());
									}
								}

							} else if('metric-dimensions' === completions['type']) {
								editor.completer.getPopup().container.style.width = '300px';
								
								var key_path = Devblocks.cerbCodeEditor.getKataTokenPath(
									null,
									editor
								);
								
								key_path.pop();
								key_path.push('metric_name:');
								
								var key_row = Devblocks.cerbCodeEditor.getKataRowByPath(editor, key_path.join(''));
								var key_line = editor.session.getLine(key_row);
								var matches = key_line.match(/[^:]*:\s*(.*)/i);
								
								if(Array.isArray(matches) && 2 === matches.length) {
									formData = new FormData();
									formData.set('c', 'ui');
									formData.set('a', 'kataSuggestionsMetricDimensionJson');
									formData.set('prefix', prefix);
									formData.set('params[metric]', matches[1]);
								}
							}

							if(null === formData) {
								return callback(null, []);
								
							} else {
								genericAjaxPost(formData, '', '', function (json) {
									if (Array.isArray(json)) {
										return callback(null, autocompleterKata.formatSuggestions(json));
									}

									return callback(null, []);
								});
							}
						} 
					}
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					editor.completer.autoSelect = false;
					editor.completer.getPopup().container.style.width = '300px';
					
					// Check for Twig autocompletion first
					
					var TokenIterator = require('ace/token_iterator').TokenIterator;
					var iter = new TokenIterator(editor.session, pos.row, pos.column);

					// Don't autocomplete comments
					if(Devblocks.cerbCodeEditor.lineIsComment(pos.row, editor))
						return;
					
					let kataAnnotations = [
						'base64',
						'bit',
						'bool',
						'csv',
						'date',
						'float',
						'int',
						'json',
						'kata',
						'key',
						'list',
						'optional',
						'ref',
						'text',
						'trim',
					];
					
					do {
						var token = iter.getCurrentToken();
						
						if(null != token) {
							if ('meta.tag' === token.type) {
								// Autocomplete @annotations
								if(-1 !== token.value.indexOf('@')) {
									if('@:' === token.value.substring(token.value.length-2)) {
										let value_prefix = token.value.substring(0, token.value.length-2).trimStart();
										
										let completions = kataAnnotations.map(function(completion) {
											return value_prefix + '@' + completion;
										});
										
										return callback(null, autocompleterKata.formatSuggestions(completions)); 
									
									} else if(',:' === token.value.substring(token.value.length-2)) {
										let completions = kataAnnotations.map(function(completion) {
											return completion;
										});
										
										return callback(null, autocompleterKata.formatSuggestions(completions)); 
									}
								}
								break;
								
							} else if ('meta.tag.twig' === token.type && '%}' === token.value) {
								break;
								
							} else if ('meta.tag.twig' === token.type && '{%' === token.value) {
								return callback(null, autocompleterKata.formatSuggestions(twigAutocompleteSuggestions.tags));
								
							} else if ('variable.other.readwrite.local.twig' === token.type && '{{' === token.value) {
								return callback(null, autocompleterKata.formatSuggestions(twigAutocompleteSuggestions.functions));
								
							} else if ('variable.other.readwrite.local.twig' === token.type && '}}' === token.value) {
								break;
								
							} else if ('keyword.operator.other.pipe' === token.type) {
								return callback(null, autocompleterKata.formatSuggestions(twigAutocompleteSuggestions.filters));
							}
						}
						
					} while(iter.stepBackward());

					var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor);
					
					// Normalize path (remove key names and annotations)
					token_path = token_path.map(function(v) {
						var pos = v.indexOf('@');
						
						if(-1 !== pos) {
							v = v.substr(0, pos) + ':';
						}
						
						pos = v.indexOf('/');
						
						if(-1 !== pos) {
							v = v.substr(0, pos) + ':';
						}

						return v; 
					});
					
					var scope_key = token_path.join('');

					// Simple static path full match
					if(editor.completer.autocomplete_suggestions.hasOwnProperty(scope_key)) {
						autocompleterKata.parseCompletions(callback, editor, scope_key, prefix);

					} else if (editor.completer.autocomplete_suggestions.hasOwnProperty('*')) {
						var regexps = editor.completer.autocomplete_suggestions['*'];
						
						for(var regexp in regexps) {
							if(scope_key.match(new RegExp('^' + regexp + '$'))) {
								editor.completer.autocomplete_suggestions[scope_key] = regexps[regexp];
								autocompleterKata.parseCompletions(callback, editor, scope_key, prefix);
								return;
							}
						}
						
						// Negative lookup cache
						editor.completer.autocomplete_suggestions[scope_key] = [];
						
						return callback(false);

					} else {
						return callback(false);
					}
				}
			};
			
			editor.setOption('enableBasicAutocompletion', []);
			editor.completers.push(autocompleterKata);
			editor.commands.on('afterExec', $.debounce(250, doCerbLiveAutocomplete));
		});
	}

	$.fn.cerbTextEditorAutocompleteComments = function() {
		var mentions_cache = null;

		return this.each(function() {
			var $editor = $(this);
			var editor = $editor[0];

			$editor.autocomplete({
				appendTo: $editor.parent(),
				autoFocus: true,
				delay: 150,

				_sourceMentions: function(request, response, token) {
					var term = token.substring(1).toLowerCase();
					var steps = [];

					steps.push(function(callback) {
						// Check local cache
						if(mentions_cache && Array.isArray(mentions_cache)) {
							return callback(null, mentions_cache);
						}

						genericAjaxGet('', 'c=ui&a=getMentionsJson', function(json) {
							if ('object' != typeof json) {
								return callback(true);
							}

							mentions_cache = json;
							return callback(null, json);
						});
					});

					async.series(steps, function(err, results) {
						if(err || results.length !== 1)
							return response([]);

						return response(results[0].filter(function(mention) {
							if(mention.label.toLowerCase().startsWith(term))
								return true;

							if(mention.value.toLowerCase().startsWith('@' + term))
								return true;

							return false;
						}));
					});
				},

				source: function(request, response) {
					var token = $editor.cerbTextEditor('getCurrentWord');

					if(token.startsWith('@')) {
						return this.options._sourceMentions(request, response, token);
					} else {
						response([]);
					}

				},

				select: function(event, ui)  {
					event.stopPropagation();
					event.preventDefault();
					$editor.cerbTextEditor('replaceCurrentWord', ui.item.value);
					return false;
				},

				focus: function(event, ui) {
					return false;
				},

				open: function(event, ui) {
					var $menu = $editor.autocomplete('widget');
					var pos = getCaretCoordinates(editor, editor.selectionEnd);

					$menu
						.css('width', '400px')
						.css('top', (editor.offsetTop - editor.scrollTop + pos.top + 15) + 'px')
						.css('left', (editor.offsetLeft - editor.scrollLeft + pos.left + 5) + 'px')
					;
				},
				
				close: function(event) {
					if('autocompleteclose' === event.type)
						event.originalEvent.stopPropagation();
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				var $li = $('<li/>');
				let $wrapper = $('<div/>').appendTo($li);

				if(item.image_url) {
					$('<img/>')
						.addClass('cerb-avatar')
						.attr('src', item.image_url)
						.attr('loading', 'lazy')
						.appendTo($wrapper)
					;
				}

				$('<span/>')
					.text(item.label)
					.css('font-weight', 'bold')
					.appendTo($wrapper)
					;

				if(item.mention) {
					$('<span/>')
						.text(item.mention)
						.css('margin-left', '10px')
						.appendTo($wrapper)
					;
				}

				if(item.title) {
					$('<span/>')
						.text(item.title)
						.css('margin-left', '10px')
						.css('font-weight', 'normal')
						.appendTo($wrapper)
					;
				}

				$li.appendTo(ul);

				return $li;
			};

			$editor.on('click', function(e) {
				if($editor.autocomplete('widget').is(':visible')) {
					$editor.autocomplete('search');
				}
			});
		});
	}

	$.fn.cerbTextEditorAutocompleteReplies = function(options) {
		if(undefined == options)
			options = {};

		var mentions_cache = null;

		return this.each(function() {
			var $editor = $(this);
			var editor = $editor[0];

			$editor.autocomplete({
				appendTo: $editor.parent(),
				autoFocus: true,
				delay: 150,

				_sourceCommand: function(request, response, token) {
					var commands = [
						{
							label: '#attach',
							value: '#attach ',
							description: 'Attach a file bundle by alias'
						},
						{
							label: '#comment',
							value: '#comment ',
							description: 'Add a ticket comment with @mention notifications'
						},
						{
							label: '#cut',
							value: '#cut\n',
							description: 'Ignore everything below this line'
						},
						{
							label: '#delete_quote_from_here',
							value: '#delete_quote_from_here',
							description: 'Remove remaining quoted text from this line'
						},
						{
							label: '#original_message',
							value: '#original_message',
							description: 'Insert the full original message placeholder'
						},
						{
							label: '#signature',
							value: '#signature\n',
							description: 'Insert the signature placeholder'
						},
						{
							label: '#snippet',
							value: '#snippet ',
							description: 'Insert a snippet'
						},
						{
							label: '#start comment',
							value: '#start comment\nYour multiple line comment goes here.\n#end\n',
							description: 'Add a multiple line ticket comment with @mention notifications'
						},
						{
							label: '#start note',
							value: '#start note\nYour multiple line sticky note goes here.\n#end\n',
							description: 'Add a multiple line sticky note with @mention notifications'
						},
						{
							label: '#unwatch',
							value: '#unwatch\n',
							description: 'Stop watching this ticket'
						},
						{
							label: '#watch',
							value: '#watch\n',
							description: 'Start watching this ticket'
						}
					];

					// Filter
					if(token.length > 1) {
						return response(commands.filter(function (command) {
							return command.label.startsWith(token);
						}));
					}

					return response(commands);
				},

				_sourceMentions: function(request, response, token) {
					var term = token.substring(1).toLowerCase();
					var steps = [];

					steps.push(function(callback) {
						// Check local cache
						if(mentions_cache && Array.isArray(mentions_cache)) {
							return callback(null, mentions_cache);
						}

						genericAjaxGet('', 'c=ui&a=getMentionsJson', function(json) {
							if ('object' != typeof json) {
								return callback(true);
							}

							mentions_cache = json;
							return callback(null, json);
						});
					});

					async.series(steps, function(err, results) {
						if(err || results.length !== 1)
							return response([]);

						return response(results[0].filter(function(mention) {
							if(mention.label.toLowerCase().startsWith(term))
								return true;

							if(mention.value.toLowerCase().startsWith('@' + term))
								return true;

							return false;
						}));
					});
				},

				_sourceSnippet: function(request, response, token) {
					var term = token;
					var ajax_requests = [];

					// [TODO] Sort by myUses
					ajax_requests.push(function(callback) {
						var types = 'reply' === options.mode ? '[plaintext,ticket,worker]' : '[plaintext,worker]';

						var query = 'type:worklist.records of:snippet query:(type:' + types
							+ (term.length === 0
								? ' '
								: ' title:"*{}*"'.replace(/\{\}/g, term)
							)
							+ ' usableBy.worker:me sort:-totalUses)'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var snippet = json.data[i];

								results.push({
									_type: 'snippet',
									label: snippet['_label'],
									value: '#snippet ' + snippet['title'],
									id: snippet['id']
								});
							}

							return callback(null, results);
						});
					});

					async.parallelLimit(ajax_requests, 2, function(err, json) {
						if(err)
							return response([]);

						var results = json.reduce(function(arr,val) { return arr.concat(val); });

						return response(results);
					});
				},

				_sourceAttach: function(request, response, token) {
					var term = token;
					var ajax_requests = [];

					ajax_requests.push(function(callback) {
						var query = 'type:worklist.records of:file_bundle query:('
							+ (term.length === 0
								? ' '
								: ' name:"*{}*"'.replace(/\{\}/g, term)
							)
							+ ' usableBy.worker:me)'
						;

						genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
							if ('object' != typeof json || !json.hasOwnProperty('data')) {
								return callback(null, []);
							}

							var results = [];

							for (var i in json.data) {
								var bundle = json.data[i];

								results.push({
									_type: 'file_bundle',
									label: bundle['_label'],
									value: '#attach ' + bundle['tag'],
									id: bundle['id']
								});
							}

							return callback(null, results);
						});
					});

					async.parallelLimit(ajax_requests, 2, function(err, json) {
						if(err)
							return response([]);

						var results = json.reduce(function(arr,val) { return arr.concat(val); });

						return response(results);
					});
				},

				source: function(request, response) {
					var token = $editor.cerbTextEditor('getCurrentWord');
					var line = $editor.cerbTextEditor('getCurrentLine');

					var snippet_pos = line.indexOf('#snippet ');

					if(-1 !== snippet_pos) {
						return this.options._sourceSnippet(request, response, line.substring(snippet_pos + 9));
					} else if(line.startsWith('#attach ')) {
						return this.options._sourceAttach(request, response, line.substring(8));
					} else if(token.startsWith('#')) {
						return this.options._sourceCommand(request, response, token);
					} else if(token.startsWith('@')) {
						return this.options._sourceMentions(request, response, token);
					} else {
						response([]);
					}
				},

				select: function(event, ui)  {
					event.stopPropagation();
					event.preventDefault();

					if(ui.item.value.startsWith('#snippet ')) {
						if(ui.item.value === '#snippet ') {
							$editor.cerbTextEditor('replaceCurrentWord', ui.item.value);

							setTimeout(function() {
								$editor.autocomplete('search');
							}, 50);

						} else {
							$editor.autocomplete('close');

							// Select everything from `#snippet` on the current line
							var line_pos = $editor.cerbTextEditor('getCurrentLinePos');
							var line = editor.value.substring(line_pos.start, line_pos.end);

							line_pos.start = line_pos.start + line.indexOf('#snippet ');

							$editor.cerbTextEditor('setSelection', line_pos.start, line_pos.end);
							$editor.cerbTextEditor('replaceSelection', '');

							var $editor_toolbar = $editor.prevAll('.cerb-code-editor-toolbar');

							$editor_toolbar.triggerHandler(new $.Event('cerb-editor-toolbar-snippet-inserted', {
								'snippet_id': ui.item.id
							}));
						}

					} else if(ui.item.value.startsWith('#attach ')) {
						if(ui.item.value === '#attach ') {
							$editor.cerbTextEditor('replaceCurrentLine', ui.item.value);

							setTimeout(function() {
								$editor.autocomplete('search');
							}, 50);

						} else {
							$editor.autocomplete('close');
							$editor.cerbTextEditor('replaceCurrentLine', ui.item.value);
						}

					} else if('#delete_quote_from_here' === ui.item.value) {
						var start = $editor.cerbTextEditor('getCurrentWordPos').start;
						var value = $editor.val();

						var lines = value.substring(start).split(/\r?\n/g);
						var remainder = [];
						var finished = false;

						for (var i in lines) {
							if (!finished && (0 == i || lines[i].startsWith('>'))) {
								continue;
							} else {
								finished = true;
							}

							remainder.push(lines[i]);
						}

						$editor.cerbTextEditor('setSelection', start, value.length);
						$editor.cerbTextEditor('replaceSelection', remainder.join('\n'));
						$editor.cerbTextEditor('setCursorPosition', start);

					} else {
						$editor.cerbTextEditor('replaceCurrentWord', ui.item.value);
					}

					return false;
				},

				focus: function(event, ui) {
					return false;
				},

				open: function(event, ui) {
					var $menu = $editor.autocomplete('widget');
					var pos = getCaretCoordinates(editor, editor.selectionEnd);

					$menu
						.css('width', '400px')
						.css('top', (editor.offsetTop - editor.scrollTop + pos.top + 15) + 'px')
						.css('left', (editor.offsetLeft - editor.scrollLeft + pos.left + 5) + 'px')
					;
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				var $li = $('<li/>');

				// #commands
				if(item.label.startsWith('#')) {
					let $wrapper = $('<div/>')
						.append($('<b/>').text(item.label))
						.appendTo($li)
					;

					if(item.description) {
						$('<span/>')
							.text(item.description)
							.css('display', 'block')
							.css('margin-left', '10px')
							.css('font-weight', 'normal')
							.appendTo($wrapper)
						;
					}

				// @mentions
				} else {
					let $wrapper = $('<div/>').appendTo($li);
					
					if(item.image_url) {
						$('<img/>')
							.addClass('cerb-avatar')
							.attr('src', item.image_url)
							.attr('loading', 'lazy')
							.appendTo($wrapper)
						;
					}

					$('<span/>')
						.text(item.label)
						.css('font-weight', 'bold')
						.appendTo($wrapper)
					;

					if(item.mention) {
						$('<span/>')
							.text(item.mention)
							.css('margin-left', '10px')
							.appendTo($wrapper)
						;
					}

					if(item.title) {
						$('<span/>')
							.text(item.title)
							.css('margin-left', '10px')
							.css('font-weight', 'normal')
							.appendTo($wrapper)
						;
					}
				}

				$li.appendTo(ul);

				return $li;
			};

			$editor.on('click', function(e) {
				if($editor.autocomplete('widget').is(':visible')) {
					$editor.autocomplete('search');
				}
			});

			$editor.bind('keydown', 'ctrl+shift+.', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$editor.cerbTextEditor('insertText', '#snippet ');
				$editor.autocomplete('search');
			});
		});
	}

	$.fn.cerbCodeEditorAutocompleteSearchQueries = function(autocomplete_options) {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();

			if(!e.editor.completer) {
				var Autocomplete = require('ace/autocomplete').Autocomplete;
				e.editor.completer = new Autocomplete();
			}

			if('Submit' === e.command.name) {
				e.editor.completer.getPopup().hide();
				return;
			}

			if('insertstring' === e.command.name) {
				if(!e.editor.completer.activated || e.editor.completer.isDynamic) {
					if(1 === e.args.length) {
						e.editor.completer.showPopup(e.editor);
					}
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}

			editor.completer.autocomplete_suggestions = {
				'_contexts': {}
			};
			
			if(autocomplete_options && autocomplete_options.context)
				editor.completer.autocomplete_suggestions._contexts[''] = autocomplete_options.context;
			
			$editor.on('cerb-code-editor-change-context', function(e, context) {
				e.stopPropagation();
				
				var editor = ace.edit($(this).attr('id'));
				
				if(!editor.completer || !editor.completer.autocomplete_suggestions)
					return;
				
				editor.completer.autocomplete_suggestions = {
					'_contexts': {
						'': context || ''
					}
				};
			});
			
			var completer = {
				identifierRegexps: [/[a-zA-Z_0-9\*\#\@\.\$\-\u00A2-\uFFFF]/],
				formatData: function(scope_key) {
					return editor.completer.autocomplete_suggestions[scope_key].map(function(data) {
						if('object' == typeof data) {
							if(!data.hasOwnProperty('score'))
								data.score = 1000;
							
							data.completer = {
								insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
							};
							return data;
							
						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000,
								completer: {
									insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
								}
							};
						}
					});
				},
				returnCompletions: function(editor, session, pos, prefix, callback) {
					var token = session.getTokenAt(pos.row, pos.column);
					
					// Don't give suggestions inside Twig elements or at the end of `)` sets
					if(token) {
						if(
							('paren.rparen' === token.type)
							|| 'variable.other.readwrite.local.twig' === token.type
							|| ('keyword.operator.other' === token.type && token.value === '|')
						){
							callback(false);
							return;
						}
					}

					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, editor);
					var scope_key = token_path.scope.join('');

					autocomplete_suggestions = editor.completer.autocomplete_suggestions;
					
					editor.completer.isDynamic = false;

					// Do we need to lazy load?
					if(autocomplete_suggestions.hasOwnProperty(scope_key)) {
						if(Array.isArray(autocomplete_suggestions[scope_key])) {
							var results = completer.formatData(scope_key);
							callback(null, results);
							
						} else if('object' == typeof autocomplete_suggestions[scope_key] 
							&& autocomplete_suggestions[scope_key].hasOwnProperty('_type')) {
							
							var key = autocomplete_suggestions[scope_key].hasOwnProperty('key') ? autocomplete_suggestions[scope_key].key : null;
							var limit = autocomplete_suggestions[scope_key].hasOwnProperty('limit') ? autocomplete_suggestions[scope_key].limit : 0;
							var min_length = autocomplete_suggestions[scope_key].hasOwnProperty('min_length') ? autocomplete_suggestions[scope_key].min_length : 0;
							var query = autocomplete_suggestions[scope_key].query.replace('{{term}}', prefix);
							
							if(min_length && prefix.length < min_length) {
								callback(null, []);
								return;
							}
							
							genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
								var results = [];

								if('object' != typeof json || !json.hasOwnProperty('data')) {
									callback('error');
									return;
								}
								
								for(var i in json.data) {
									if(!json.data[i].hasOwnProperty(key) || 0 === json.data[i][key].length)
										continue;
									
									var value = json.data[i][key];
									
									results.push({
										caption: value,
										value: -1 !== value.indexOf(' ') ? ('"' + value + '"') : value,
										completer: {
											insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
										}
									});
								}
								
								// If we have the full set, persist it
								if('' === prefix && limit && limit > json.data.length) {
									autocomplete_suggestions[scope_key] = results;
									
								} else {
									editor.completer.isDynamic = true;
								}

								callback(null, results);
							});
						}
						
					} else {
						// If pasting or existing value, work backwards
						
						(function() {
							var expand = '';
							var expand_prefix = '';
							var expand_context = autocomplete_suggestions._contexts[''] || '';
							
							if(autocomplete_suggestions[scope_key]) {
								editor.completer.showPopup(editor);
								return;
								
							} else if(autocomplete_suggestions._contexts && autocomplete_suggestions._contexts.hasOwnProperty(scope_key)) {
								expand_context = autocomplete_suggestions._contexts[scope_key];
								expand_prefix = scope_key;
								
							} else {
								var stack = [];
								
								for(key in token_path.scope) {
									stack.push(token_path.scope[key]);
									var stack_key = stack.join('');
									
									if(autocomplete_suggestions[stack_key]) {
										expand_prefix += token_path.scope[key];
										
									} else if (autocomplete_suggestions._contexts && autocomplete_suggestions._contexts[stack_key]) {
										expand_context = autocomplete_suggestions._contexts[stack_key];
										expand_prefix += token_path.scope[key];
										
									} else {
										expand += token_path.scope[key];
									}
								}
							}
							
							if('' === expand_context) {
								callback(null, []);
								return;
							}
							
							// [TODO] localStorage cache?
							genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
								if('object' != typeof json) {
									callback(null, []);
									return;
								}
								
								for(var path_key in json) {
									if(path_key === '_contexts') {
										if(!autocomplete_suggestions['_contexts'])
											autocomplete_suggestions['_contexts'] = {};
										
										for(var context_key in json[path_key]) {
											autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
										}
										
									} else {
										autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
									}
								}
								
								if(autocomplete_suggestions[scope_key]) {
									editor.completer.showPopup(editor);
									
								} else {
									callback(null, []);
								}
								return;
							});
						})();
					}
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					editor.completer.autoSelect = false;
					completer.returnCompletions(editor, session, pos, prefix, callback);
				}
			};
		
			editor.setOption('enableBasicAutocompletion', []);
			editor.completers.push(completer);
			editor.commands.on('afterExec', $.debounce(250, doCerbLiveAutocomplete));
		});
	};

	$.fn.cerbCodeEditorAutocompleteDataQueries = function() {
		var Autocomplete = require('ace/autocomplete').Autocomplete;
		
		var autocomplete_scope = {
			'type': '',
			'of': ''
		};
		
		var autocomplete_suggestions = [];
		var autocomplete_contexts = [];
		
		var autocomplete_suggestions_types = {
			'': [
				'type:'
			],
			'type:': []
		};
		
		var doCerbLiveAutocomplete = function(e) {
			e.stopPropagation();

			if (!(
				'insertstring' === e.command.name
				|| 'paste' === e.command.name
				|| 'Submit' === e.command.name
				|| 'backspace' === e.command.name)) {
				return;
			}

			if (!e.editor.completer) {
				e.editor.completer = new Autocomplete();
			}

			var value = e.editor.session.getValue();
			var pos = e.editor.getCursorPosition();
			var current_field = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor, 1);
			var is_dirty = false;

			// If we're in the middle of typing a dynamic series alias, ignore it
			if (1 === current_field.scope.length
				&& 0 === current_field.nodes.length
				&& -1 !== ['series.', 'values.'].indexOf(current_field.scope[0].substr(0, 7))
			) {
				return;
			}

			if (0 === value.length) {
				autocomplete_suggestions = {};
				autocomplete_scope.type = '';
				autocomplete_scope.of = '';
				is_dirty = true;

			// If we pasted content, rediscover the scope
			} else if ('paste' === e.command.name) {
				autocomplete_suggestions = {};
				autocomplete_scope.type = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'type:') || '';
				autocomplete_scope.of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'of:') || '';
				is_dirty = true;

			// If we're typing
			} else if (current_field.hasOwnProperty('scope')) {
				var current_field_name = current_field.scope.slice(-1)[0];

				if (current_field_name === 'type:' && current_field.nodes[0]) {
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor);

					if (1 === token_path.scope.length) {
						var type = current_field.nodes[0].value;

						if (autocomplete_scope.type !== type) {
							autocomplete_scope.type = type;
							autocomplete_scope.of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(e.editor, 'of:') || '';
							is_dirty = true;
						}
					}

				} else if (current_field_name === 'of:' && current_field.nodes[0]) {
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, e.editor);

					if (1 === token_path.scope.length) {
						var of = current_field.nodes[0].value;

						if (autocomplete_scope.of !== of) {
							autocomplete_scope.of = of;

							// If it's not a known context, ignore
							if (-1 !== autocomplete_contexts.indexOf(of)) {
								is_dirty = true;
							}
						}

					} else if (-1 !== ['series.', 'values.'].indexOf(token_path.scope[0].substr(0, 7))) {
						var series_key = token_path.scope[0];
						var series_of = token_path.nodes[0].value;

						if (autocomplete_scope[series_key + 'of:'] !== series_of) {
							autocomplete_scope[series_key + 'of:'] = series_of;

							for(var key in autocomplete_suggestions._contexts) {
								if(series_key === key.substr(0,series_key.length))
									autocomplete_suggestions._contexts[key] = null;
							}

							for (var key in autocomplete_suggestions) {
								if (series_key === key.substr(0, series_key.length))
									autocomplete_suggestions[key] = null;
							}

							if (-1 !== autocomplete_contexts.indexOf(series_of)) {
								autocomplete_scope[series_key + 'x:'] = {
									'_type': 'series_of_field'
								};

								autocomplete_scope[series_key + 'y:'] = {
									'_type': 'series_of_field'
								};

								autocomplete_scope[series_key + 'query:'] = {
									'_type': 'series_of_query'
								};

								autocomplete_scope[series_key + 'query.required:'] = {
									'_type': 'series_of_query'
								};
							}
						}
					}
				}
			}

			if (is_dirty) {
				var type = autocomplete_scope.type;
				var of = autocomplete_scope.of;

				// If type: is invalid
				if ('' === type || -1 === autocomplete_suggestions_types['type:'].indexOf(type)) {
					autocomplete_suggestions = autocomplete_suggestions_types;

				} else {
					genericAjaxGet('', 'c=ui&a=dataQuerySuggestions&type=' + encodeURIComponent(type) + '&of=' + encodeURIComponent(of), function (json) {
						if ('object' == typeof json) {
							autocomplete_suggestions = json;
						} else {
							autocomplete_suggestions = autocomplete_suggestions_types;
						}
					});
				}
			}

			if ('Submit' === e.command.name) {
				e.editor.completer.getPopup().hide();

			} else if((!e.editor.completer.activated || e.editor.completer.isDynamic)) {
				if(e.args && 1 === e.args.length) {
					e.editor.completer.showPopup(e.editor);
				}
			}
		};
		
		return this.each(function() {
			var $editor = $(this)
				.nextAll('pre.ace_editor')
				;
				
			var editor = ace.edit($editor.attr('id'));
			
			if(!editor.completer) {
				editor.completer = new Autocomplete();
			}

			var completer = {
				identifierRegexps: [/[a-zA-Z_0-9\*\#\@\.\$\-\u00A2-\uFFFF]/],
				formatData: function(scope_key) {
					if(!autocomplete_suggestions.hasOwnProperty(scope_key)
						|| undefined == autocomplete_suggestions.hasOwnProperty(scope_key))
						return [];
					
					return autocomplete_suggestions[scope_key].map(function(data) {
						if('object' == typeof data) {
							if(!data.hasOwnProperty('score'))
								data.score = 1000;
							
							data.completer = {
								insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
							};
							return data;
							
						} else if('string' == typeof data) {
							return {
								caption: data,
								snippet: data,
								score: 1000,
								completer: {
									insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
								}
							};
						}
					});
				},
				getCompletions: function(editor, session, pos, prefix, callback) {
					editor.completer.autoSelect = false;

					var token = session.getTokenAt(pos);
					
					// Don't give suggestions inside Twig elements
					if(token) {
						if(
							'variable.other.readwrite.local.twig' === token.type
							|| ('keyword.operator.other' === token.type && token.value === '|')
						){
							callback(false);
							return;
						}
					}
					
					var token_path = Devblocks.cerbCodeEditor.getQueryTokenPath(pos, editor);
					var scope_key = token_path.scope.join('');
					
					if(!(autocomplete_suggestions instanceof Object)) {
						callback(false);
						return;
					}
					
					if(autocomplete_suggestions[scope_key]) {
						if(Array.isArray(autocomplete_suggestions[scope_key])) {
							var results = completer.formatData(scope_key);
							callback(null, results);
							
						} else if('object' == typeof autocomplete_suggestions[scope_key] 
							&& autocomplete_suggestions[scope_key].hasOwnProperty('_type')) {
							
							if('autocomplete' === autocomplete_suggestions[scope_key]._type) {
								var key = autocomplete_suggestions[scope_key].hasOwnProperty('key') ? autocomplete_suggestions[scope_key].key : null;
								var limit = autocomplete_suggestions[scope_key].hasOwnProperty('limit') ? autocomplete_suggestions[scope_key].limit : 0;
								var min_length = autocomplete_suggestions[scope_key].hasOwnProperty('min_length') ? autocomplete_suggestions[scope_key].min_length : 0;
								var query = autocomplete_suggestions[scope_key].query.replace('{{term}}', prefix);
								
								if(min_length && prefix.length < min_length) {
									callback(null, []);
									return;
								}
								
								genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(query), function(json) {
									var results = [];

									if('object' != typeof json || !json.hasOwnProperty('data')) {
										callback(null, []);
										return;
									}
									
									for(var i in json.data) {
										if(!json.data[i].hasOwnProperty(key) || 0 === json.data[i][key].length)
											continue;
										
										var value = json.data[i][key];
										
										results.push({
											caption: value,
											value: -1 != value.indexOf(' ') ? ('"' + value + '"') : value,
											completer: {
												insertMatch: Devblocks.cerbCodeEditor.insertMatchAndAutocomplete
											}
										});
									}
									
									// If we have the full set, persist it
									if('' === prefix && limit && limit > json.data.length) {
										autocomplete_suggestions[scope_key] = results;
										
									} else {
										editor.completer.isDynamic = true;
									}
									
									callback(null, results);
									return;
								});
								
							} else if('series_of_query' === autocomplete_suggestions[scope_key]._type) {
								var of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
								
								if(!of) {
									callback(null, []);
									return;
								}
								
								genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(of), function(json) {
									if('object' != typeof json) {
										callback(null, []);
										return;
									}
									
									var path_keys = Object.keys(json);
									
									for(var path_key_idx in path_keys) {
										var path_key = path_keys[path_key_idx];
										
										if(path_key === '_contexts') {
											if(!autocomplete_suggestions.hasOwnProperty('_contexts'))
												autocomplete_suggestions['_contexts'] = {};
											
											var context_keys = Object.keys(json[path_key]);
											
											for(context_key_id in context_keys) {
												var context_key = context_keys[context_key_id];
												autocomplete_suggestions['_contexts'][scope_key + context_key] = json[path_key][context_key];
											}
											
										} else {
											autocomplete_suggestions[scope_key + path_key] = json[path_key];
										}
									}
									
									var results = completer.formatData(scope_key);
									callback(null, results);
									return;
								});
								
							} else if('series_of_field' === autocomplete_suggestions[scope_key]._type) {
								var of = autocomplete_scope[token_path.scope[0] + 'of:']
									|| Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
								
								if(!of) {
									callback(null, []);
									return;
								}
								
								var of_types = autocomplete_suggestions[scope_key].of_types || '';
								
								genericAjaxGet('', 'c=ui&a=queryFieldSuggestions&of=' + encodeURIComponent(of) + '&types=' + encodeURIComponent(of_types), function(json) {
									if(!Array.isArray(json)) {
										callback(null, []);
										return;
									}
									
									autocomplete_suggestions[scope_key] = json;
									
									var results = completer.formatData(scope_key);
									callback(null, results);
									return;
								});
							}
						}
						
					} else {
						if(
							('object' == typeof token_path && Array.isArray(token_path.scope)) // && 'object' == typeof token_path.scope[0]
 							&& (
								('series.' == token_path.scope[0].substr(0,7) && !autocomplete_suggestions[token_path.scope[0]] && autocomplete_suggestions['series.*:'])
								||
								('values.' == token_path.scope[0].substr(0,7) && !autocomplete_suggestions[token_path.scope[0]] && autocomplete_suggestions['values.*:'])
							)) {
							var series_key = token_path.scope[0];
							var series_template_key = token_path.scope[0].substr(0,7) + '*:';
							
							for(var suggest_key in autocomplete_suggestions[series_template_key]) {
								if(autocomplete_suggestions[series_template_key][suggest_key]) {
									autocomplete_suggestions[series_key + suggest_key] = autocomplete_suggestions[series_template_key][suggest_key];
								}
							}
							var series_of = Devblocks.cerbCodeEditor.getQueryTokenValueByPath(editor, token_path.scope[0] + 'of:');
							
							if(series_of && token_path.scope[1] && 'query' === token_path.scope[1].substr(0,5)) {
								if(!autocomplete_suggestions['_contexts'])
									autocomplete_suggestions['_contexts'] = {};
								
								autocomplete_suggestions['_contexts'][token_path.scope.slice(0,2).join('')] = series_of;
								
								// Load the series context
								
								var expand_context = series_of;
								var expand_prefix = token_path.scope.slice(0,2).join('');
								var expand = token_path.scope.slice(2).join('');
								
								genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
									if('object' != typeof json) {
										callback(null, []);
										return;
									}
									
									for(path_key in json) {
										if(path_key === '_contexts') {
											if(!autocomplete_suggestions['_contexts'])
												autocomplete_suggestions['_contexts'] = {};
											
											for(var context_key in json[path_key]) {
												autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
											}
											
										} else {
											autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
										}
									}
									
									if(autocomplete_suggestions[scope_key]) {
										editor.completer.showPopup(editor);

									} else {
										callback(null, []);
									}
									return;
								});

								editor.completer.showPopup(editor);
								return;

							} else if(1 === token_path.scope.length) {
								editor.completer.showPopup(editor);
								return;
							}
						}

						(function() {
							var expand = '';
							var expand_prefix = '';
							var expand_context = '';

							if(autocomplete_suggestions[scope_key]) {
								editor.completer.showPopup(editor);
								return;
								
							} else if(autocomplete_suggestions._contexts && autocomplete_suggestions._contexts.hasOwnProperty(scope_key)) {
								expand_context = autocomplete_suggestions._contexts[scope_key];
								expand_prefix = scope_key;
								
							} else {
								var stack = [];
								
								for(key in token_path.scope) {
									stack.push(token_path.scope[key]);
									var stack_key = stack.join('');
									
									if(autocomplete_suggestions[stack_key]) {
										expand_prefix += token_path.scope[key];
										
									} else if (autocomplete_suggestions._contexts && autocomplete_suggestions._contexts[stack_key]) {
										expand_context = autocomplete_suggestions._contexts[stack_key];
										expand_prefix += token_path.scope[key];
										
									} else {
										expand += token_path.scope[key];
									}
								}
							}
							
							genericAjaxGet('', 'c=ui&a=querySuggestions&context=' + encodeURIComponent(expand_context) + '&expand=' + encodeURIComponent(expand), function(json) {
								if('object' != typeof json) {
									callback(null, []);
									return;
								}
								
								for(path_key in json) {
									if(path_key === '_contexts') {
										if(!autocomplete_suggestions['_contexts'])
											autocomplete_suggestions['_contexts'] = {};
										
										for(var context_key in json[path_key]) {
											autocomplete_suggestions['_contexts'][expand_prefix + context_key] = json[path_key][context_key];
										}
										
									} else {
										autocomplete_suggestions[expand_prefix + path_key] = json[path_key];
									}
								}
								
								if(autocomplete_suggestions[scope_key]) {
									editor.completer.showPopup(editor);
								} else {
									callback(null, []);
								}
								return;
							});
						})();
					}
				}
			};
			
			(function() {
				var cerbQuerySuggestionMeta = null;
				
				if(localStorage && localStorage.cerbQuerySuggestionMeta) {
					try {
						cerbQuerySuggestionMeta = JSON.parse(localStorage.cerbQuerySuggestionMeta);
					} catch(ex) {
						cerbQuerySuggestionMeta = null;
					}
				}
				
				// Only run this once everything is ready
				var editor_callback = function() {
					autocomplete_suggestions = autocomplete_suggestions_types;
					
					editor.setOption('enableBasicAutocompletion', []);
					editor.commands.on('afterExec', $.debounce(250, doCerbLiveAutocomplete));
					editor.completers.push(completer);
					
					editor.on('focus', function(e) {
						var val = editor.getValue();
						
						if(0 === val.length) {
							if(!editor.completer) {
								editor.completer = new Autocomplete();
							}

							editor.completer.showPopup(editor);
						}
					});
					
					// If we have default content, trigger a paste
					if(editor.getValue().length > 0) {
						setTimeout(function() {
							editor.commands.exec('paste', editor, {text:''})
						}, 200);
					}
				}
				
				// Do we have a cached copy of the schema meta?
				if(cerbQuerySuggestionMeta
					&& cerbQuerySuggestionMeta.schemaVersion
					&& cerbQuerySuggestionMeta.schemaVersion == CerbSchemaRecordsVersion) {
					
					autocomplete_contexts = cerbQuerySuggestionMeta.recordTypes;
					autocomplete_suggestions_types['type:'] = cerbQuerySuggestionMeta.dataQueryTypes;
					editor_callback.call();
					
				} else {
					genericAjaxGet('', 'c=ui&a=querySuggestionMeta', function(json) {
						if('object' != typeof json)
							return;
						
						autocomplete_contexts = json.recordTypes;
						autocomplete_suggestions_types['type:'] = json.dataQueryTypes;
						
						if(localStorage)
							localStorage.cerbQuerySuggestionMeta = JSON.stringify(json);
						
						editor_callback.call();
					});
				}
			})();
		});
	};
	
	// Abstract bot interaction trigger
	
	$.fn.cerbBotTrigger = function(options) {
		if(null == options)
			options = {};
		
		return this.each(function() {
			var $trigger = $(this);
			
			// Context
			
			$trigger.on('click', function(e) {
				e.stopPropagation();
				
				var startInteraction = async function() {
					let promise = new Promise((resolve, reject) => {
						var interaction_uri = $trigger.attr('data-interaction-uri');
						var interaction = $trigger.attr('data-interaction');
						var interaction_params = $trigger.attr('data-interaction-params');
						var behavior_id = $trigger.attr('data-behavior-id');

						var layer = Devblocks.uniqueId();

						var formData = new FormData();
						formData.set('c', 'profiles');
						formData.set('a', 'invoke');
						formData.set('module', 'bot');
						formData.set('action', 'startInteraction');

						formData.set('interaction', interaction);
						formData.set('browser[url]', window.location.href);

						if(null != interaction_uri)
							formData.set('interaction_uri', interaction_uri);

						if(null != behavior_id)
							formData.set('behavior_id', behavior_id);

						if(interaction_params && interaction_params.length > 0) {
							var parts = new URLSearchParams(interaction_params);

							for(var pair of parts.entries()) {
								if('[]' === pair[0].substr(-2)) {
									formData.append('params[' + pair[0].slice(0,-2) + '][]', pair[1]);
								} else {
									// Nested
									if(!pair[0].startsWith('[') && -1 !== pair[0].indexOf(']')) {
										let firstBracket = pair[0].indexOf('[');
										pair[0] = 'params[' + pair[0].substring(0, firstBracket) + ']'
											+ pair[0].substring(firstBracket)
										formData.set(pair[0], pair[1]);
									} else {
										formData.set('params[' + pair[0] + ']', pair[1]);
									}
								}
							}
						}

						// @deprecated
						$.each(this.attributes, function() {
							if('data-interaction-param-' === this.name.substring(0,23)) {
								formData.append('params[' + this.name.substring(23) + ']', this.value);
							}
						});

						// Caller
						if(options && options.caller && 'object' == typeof options.caller) {
							if(options.caller.name)
								formData.set('caller[name]', options.caller.name);

							if(options.caller.params && 'object' == typeof options.caller.params) {
								for(var k in options.caller.params) {
									formData.set('caller[params][' + k + ']', options.caller.params[k]);
								}
							}
						}

						// Give the callback an opportunity to append
						if(options && options.start && 'function' == typeof options.start) {
							options.start(formData);
						}

						// Is the interaction inline or a popup?

						if(options && options.target) {
							formData.set('interaction_style', 'inline');

							genericAjaxPost(formData, null, null, function(json) {
								// Polymorph old HTML responses
								if('string' == typeof json) {
									var html = json;

									json = {
										'exit': 'await',
										'html': html
									};
								}

								if('object' != typeof json)
									return reject();

								if(!json.hasOwnProperty('exit'))
									return reject();

								if('return' === json.exit || 'exit' === json.exit) {
									if(options && options.done && 'function' == typeof options.done) {
										options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									if(json.return && json.return.clipboard) {
										resolve(json);
									} else {
										reject();
									}

								} else if('error' === json.exit) {
									if(options && options.error && 'function' == typeof options.error) {
										options.error($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									reject('Interaction error');
									
								} else if('await' === json.exit) {
									var $html = $('<div/>')
										.on('cerb-interaction-reset', function(e) {
											e.stopPropagation();
											if(options && options.reset && 'function' == typeof options.reset) {
												options.reset($.Event(e));
											}
										})
										.on('cerb-interaction-done', function(e) {
											e.stopPropagation();
											if(options && options.done && 'function' == typeof options.done) {
												options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
											}
										})
										.html(json.html)
									;

									if(options.target.html) {
										options.target.html($html);
									}
								}
							});

						} else {
							formData.set('layer', layer);

							// This returns JSON now to control the popup before it opens
							genericAjaxPost(formData, null, null, function(json) {
								// Polymorph old HTML responses
								if('string' == typeof json) {
									var html = json;

									json = {
										'exit': 'await',
										'html': html
									};
								}

								if('object' != typeof json)
									return reject();

								if(!json.hasOwnProperty('exit'))
									return reject();

								// Return right away without the popup
								if('return' === json.exit || 'exit' === json.exit) {
									if(options && options.done && 'function' == typeof options.done) {
										options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}
									
									if(json.return && json.return.clipboard) {
										resolve(json);
									} else {
										reject('No clipboard data');
									}
									
								} else if('error' === json.exit) {
									if(options && options.error && 'function' == typeof options.error) {
										options.error($.Event('cerb-interaction-done', { trigger: $trigger, eventData: json }));
									}

									reject('Interaction error');

								// Open a blank popup and assign content
								} else if('await' === json.exit) {
									var popup_width = options.width || '50%';

									var $popup = genericAjaxPopup(layer, null, null, options && options.modal, popup_width);

									$popup
										.on('cerb-interaction-reset', function(e) {
											e.stopPropagation();

											if(options && options.reset && 'function' == typeof options.reset) {
												options.reset($.Event(e));
											}
										})
										.on('cerb-interaction-done', function(e) {
											e.stopPropagation();

											if(options && options.done && 'function' == typeof options.done) {
												options.done($.Event('cerb-interaction-done', { trigger: $trigger, eventData: e.eventData }));
											}

											genericAjaxPopupClose($popup);
										})
										.on('peek_aborted', function(e) {
											e.stopPropagation();

											if(options && options.abort && 'function' == typeof options.abort) {
												options.abort($.Event('cerb-interaction-done', { trigger: $trigger, eventData: { } }));
											}
										})
									;

									$popup.html(json.html);

									setTimeout(function() {
										$popup.trigger('popup_open');
									},0);
								}
							});
						}
					});
					
					return await promise;
				}

				// √: Safari, Chrome, Opera, Edge
				if('function' == typeof navigator?.clipboard?.write) {
					navigator.clipboard.write([new ClipboardItem({
						'text/plain': startInteraction().then((result) => {
							return new Promise(async (resolve, reject) => {
								if(result?.return?.clipboard) {
									resolve(new Blob([result.return.clipboard], { type: 'text/plain'}));
								} else {
									reject();
								}
							});
						}).catch(function() {
						})
					})]).then(
						function() {
							// Wrote to clipboard
						},
						function(err) {
							// Failed
						}
					).catch(function(reason) { });
				
				// √: Firefox
				} else {
					// Otherwise we need to call our promise async manually
					startInteraction().then(
						function(result) {
							if(result?.return?.clipboard) {
								if('function' == typeof navigator?.clipboard?.writeText) {
									navigator.clipboard.writeText(result.return.clipboard).then(
										function() {
										},
										function() {
										}
									);
								}
							} else {
								// document.execCommand('copy')
							}
						},
						function(err) {
							// Failed the interaction
						}
					).catch(function(reason) {});
				}
			});
		});
	}

	// Abstract query builder
	
	$.fn.cerbQueryTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			if(!($trigger.is('input[type=text]')) && !($trigger.is('textarea')))
				return;
			
			$trigger
				.css('color', 'var(--cerb-color-background-contrast-100)')
				.css('cursor', 'text')
				.attr('readonly', 'readonly')
			;
			
			if(null == $trigger.attr('placeholder'))
				$trigger.attr('placeholder', '(click to edit)');
			
			// Context
			
			$trigger.on('click keypress', function(e) {
				e.stopPropagation();
				
				var width = $(window).width()-100;
				var q = $trigger.val();
				var context = $trigger.attr('data-context');
				
				if(!(typeof context == "string") || 0 === context.length)
					return;
				
				var $chooser = genericAjaxPopup("chooser" + Devblocks.uniqueId(),'c=internal&a=invoke&module=records&action=chooserOpenParams&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q),null,true,width);
				
				$chooser.on('chooser_save',function(event) {
					$trigger.val(event.worklist_quicksearch);
					
					event.type = 'cerb-query-saved';
					$trigger.trigger(event);
				});
			});
		});
	}
	
	// Abstract template builder
	
	$.fn.cerbTemplateTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this)
				.css('color', 'var(--cerb-color-background-contrast-100)')
				.css('cursor', 'text')
				.attr('readonly', 'readonly')
			;
			
			if(!($trigger.is('textarea')))
				return;
			
			$trigger.on('click', function() {
				var context = $trigger.attr('data-context');
				var label_prefix = $trigger.attr('data-label-prefix');
				var key_prefix = $trigger.attr('data-key-prefix');
				var placeholders_json = $trigger.attr('data-placeholders-json');
				var template = $trigger.val();
				var width = $(window).width()-100;
				
				// Context
				if(!(typeof context == "string") || 0 == context.length)
					return;
				
				var url = 'c=internal&a=invoke&module=records&action=editorOpenTemplate&context='
					+ encodeURIComponent(context) 
					+ '&template=' + encodeURIComponent(template)
					+ '&label_prefix=' + (label_prefix ? encodeURIComponent(label_prefix) : '')
					+ '&key_prefix=' + (key_prefix ? encodeURIComponent(key_prefix) : '')
					;

				if(typeof placeholders_json == 'string') {
					var placeholders = JSON.parse(placeholders_json);
					
					if(typeof placeholders == 'object')
					for(key in placeholders) {
						url += "&placeholders[" + encodeURIComponent(key) + ']=' + encodeURIComponent(placeholders[key]);
					}
				}
				
				var $chooser = genericAjaxPopup(
					"template" + Devblocks.uniqueId(),
					url,
					null,
					true,
					width
				);
				
				$chooser.on('template_save',function(event) {
					$trigger.val(event.template);
					event.type = 'cerb-template-saved';
					$trigger.trigger(event);
				});
			});
		});
	}

	// Abstract peeks
	
	$.fn.cerbPeekTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger.on('click', function(evt) {
				evt.preventDefault();
				evt.stopPropagation();
				
				var context = $trigger.attr('data-context');
				var context_id = $trigger.attr('data-context-id');
				var layer = $trigger.attr('data-layer');
				var width = $trigger.attr('data-width') || null;
				var edit_mode = !!$trigger.attr('data-edit');
				
				if(null == width && 'object' == typeof options)
					width = options.width || null;
				
				var profile_url = $trigger.attr('data-profile-url');
				
				if(!profile_url && (evt.shiftKey || evt.metaKey))
					edit_mode = true;
				
				// Context
				if(!(typeof context == "string") || 0 === context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 === layer.length)
					//layer = "peek" + Devblocks.uniqueId();
					layer = $.md5(context + ':' + context_id + ':' + (edit_mode ? 'true' : 'false'));
				
				if(profile_url && (evt.shiftKey || evt.metaKey)) {
					window.open(profile_url, '_blank', 'noopener');
					return;
				}
				
				var peek_url = 'c=internal&a=invoke&module=records&action=showPeekPopup&context=' + encodeURIComponent(context) + '&context_id=' + encodeURIComponent(context_id);

				// View
				if(typeof options == 'object' && options.view_id)
					peek_url += '&view_id=' + encodeURIComponent(options.view_id);
				
				// Edit mode
				if(edit_mode) {
					peek_url += '&edit=' + encodeURIComponent($trigger.attr('data-edit'));
				}
				
				if(!width)
					width = '50%';
				
				// Open peek
				var $peek = genericAjaxPopup(layer,peek_url,null,false,width);
				
				var peek_open_event = $.Event('cerb-peek-opened');
				peek_open_event.peek_layer = layer;
				peek_open_event.peek_context = context;
				peek_open_event.peek_context_id = context_id;
				peek_open_event.popup_ref = $peek;
				$trigger.trigger(peek_open_event);
				
				$peek.on('peek_saved cerb-peek-saved', function(e) {
					var is_rebroadcast = e.type === 'cerb-peek-saved';
					var save_event = $.Event(e.type, e);
					save_event.type = 'cerb-peek-saved';
					save_event.context = context;
					save_event.is_rebroadcast = is_rebroadcast;
					$trigger.trigger(save_event);

					if(e.is_new) {
						var new_event = $.Event(e.type, e);
						new_event.type = 'cerb-peek-created';
						new_event.is_rebroadcast = is_rebroadcast;
						$trigger.trigger(new_event);
					}

					e.stopPropagation();
				});

				$peek.on('peek_deleted cerb-peek-deleted', function(e) {
					var is_rebroadcast = e.type === 'cerb-peek-deleted';
					var delete_event = $.Event(e.type, e);
					delete_event.type = 'cerb-peek-deleted';
					delete_event.context = context;
					delete_event.is_rebroadcast = is_rebroadcast;
					$trigger.trigger(delete_event);

					e.stopPropagation();
				});
				
				$peek.on('peek_aborted', function(e) {
					var abort_event = $.Event(e.type, e);
					abort_event.type = 'cerb-peek-aborted';
					abort_event.context = context;
					abort_event.is_rebroadcast = e.type === 'cerb-peek-aborted';
					$trigger.trigger(abort_event);

					e.stopPropagation();
				});
				
				$peek.on('cerb-links-changed', function(e) {
					var links_event = $.Event(e.type, e);
					links_event.type = 'cerb-peek-links-changed';
					links_event.context = context;
					links_event.is_rebroadcast = e.type === 'cerb-links-changed';
					$trigger.trigger(links_event);

					e.stopPropagation();
				});
				
				$peek.closest('.ui-dialog').find('.ui-dialog-titlebar-close').on('click', function(e) {
					$trigger.trigger('cerb-peek-aborted');
				});

				$peek.on('dialogclose', function() {
					$trigger.trigger('cerb-peek-closed');
				});
			});
		});
	}
	
	// Abstract searches
	
	$.fn.cerbSearchTrigger = function(options) {
		return this.each(function() {
			var $trigger = $(this);
			
			$trigger.click(function() {
				var context = $trigger.attr('data-context');
				var layer = $trigger.attr('data-layer');
				var query = $trigger.attr('data-query');
				var query_req = $trigger.attr('data-query-required');

				// Context
				if(!(typeof context == "string") || 0 === context.length)
					return;
				
				// Layer
				if(!(typeof layer == "string") || 0 === layer.length)
					layer = "search" + Devblocks.uniqueId();
				
				var search_url = 'c=search&a=openSearchPopup&context=' + encodeURIComponent(context) + '&id=' + layer;
				
				if(typeof query == 'string' && query.length > 0) {
					search_url = search_url + '&q=' + encodeURIComponent(query);
				}
				
				if(typeof query_req == 'string' && query_req.length > 0) {
					search_url = search_url + '&qr=' + encodeURIComponent(query_req);
				}
				
				// Open search
				var $peek = genericAjaxPopup(layer,search_url,null,false,'90%');
				
				$trigger.trigger('cerb-search-opened');
				
				$peek.on('dialogclose', function(e) {
					$trigger.trigger('cerb-search-closed');
				});
			});
		});
	}

	// Image paste

	$.fn.cerbTextEditorInlineImagePaster = function(options) {
		return this.each(function() {
			var $cursor = $(this);
			var $attachments = options['attachmentsContainer'];
			var $toolbar = options['toolbar'];
			var $ul = $attachments.find('ul.chooser-container');

			$cursor.on('paste', function(e) {
				e.stopPropagation();

				var files = e.originalEvent.clipboardData.files;

				if(0 === files.length) {
					return;
				}

				e.preventDefault();

				// Uploads

				var jobs = [];
				var labels = [];
				var values = [];

				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();

					if(xhr.upload) {
						var $spinner = Devblocks.getSpinner()
							.css('max-width', '16px')
							.css('margin-right', '5px')
						;

						var $status = $('<li/>');

						$status
							.appendTo($ul)
							.append($spinner)
							.append(
								$('<span/>')
									.text('Uploading ' + f.name)
							)
						;

						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));

						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								$status.remove();

								// var json = {};

								if(xhr.status == 200) {
									var json = JSON.parse(xhr.responseText);

									var file_id = json.id;
									var file_name = json.name;
									var file_type = json.type;
									var file_size_label = '(' + json.size_label + ')';

									var url =
										document.location.protocol
										+ '//'
										+ document.location.host
										+ DevblocksWebPath
										+ 'files/'
										+ encodeURIComponent(file_id) + '/'
										+ encodeURIComponent(file_name)
									;

									// Paste at cursor
									if(file_type.lastIndexOf("image/", 0) === 0) {
										$cursor.cerbTextEditor('insertText', '![inline-image](' + url + ")\n");

										// Enable formatting if not already
										if($toolbar && 'object' == typeof $toolbar) {
											$toolbar.triggerHandler($.Event('cerb-editor-toolbar-formatting-set', { enabled: true }));
										}
									}

									// Add to attachments container
									if($ul && 0 === $ul.find('input:hidden[value="' + file_id + '"]').length) {
										var $hidden = $('<input type="hidden"/>')
											.attr('name', $attachments.find('button[data-field-name]').attr('data-field-name') + '[]')
											.val(file_id)
										;
										var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
										var $a = $('<a href="javascript:;"/>')
											.attr('data-context', 'attachment')
											.attr('data-context-id', file_id)
											.text(file_name + ' ' + file_size_label)
											.cerbPeekTrigger()
										;
										var $li = $('<li/>').append($a).append($hidden).append($remove);
										$ul.append($li);
									}
								}

								callback(null);
							}
						};

						xhr.send(f);
					}
				};

				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}

				if(0 === jobs.length)
					return;

				async.parallelLimit(jobs, 2, function(err, json) {
					//if(err)
				});
			});
		});
	}

	$.fn.cerbCodeEditorInlineImagePaster = function(options) {
		return this.each(function() {
			var $cursor = $(this);
			var $attachments = options['attachmentsContainer'];
			var $ul = $attachments.find('ul.chooser-container');

			$cursor.on('paste', function(e) {
				e.preventDefault();
				e.stopPropagation();

				// Uploads

				var jobs = [];
				var labels = [];
				var values = [];

				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();

					if(xhr.upload) {
						var $spinner = Devblocks.getSpinner()
							.css('max-width', '16px')
							.css('margin-right', '5px')
						;

						var $status = $('<li/>');

						$status
							.appendTo($ul)
							.append($spinner)
							.append(
								$('<span/>')
									.text('Uploading ' + f.name)
							)
						;

						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));

						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								$status.remove();

								// var json = {};

								if(xhr.status == 200) {
									var json = JSON.parse(xhr.responseText);

									var file_id = json.id;
									var file_name = json.name;
									var file_type = json.type;
									var file_size_label = '(' + json.size_label + ')';

									var url =
										document.location.protocol
										+ '//'
										+ document.location.host
										+ DevblocksWebPath
										+ 'files/'
										+ encodeURIComponent(file_id) + '/'
										+ encodeURIComponent(file_name)
									;

									// Paste at cursor
									if(file_type.lastIndexOf("image/", 0) === 0) {
										options['editor'].insertSnippet('![inline-image](' + url + ")\n");
									}

									// Add to attachments container
									if($ul && 0 === $ul.find('input:hidden[value="' + file_id + '"]').length) {
										var $hidden = $('<input type="hidden"/>')
											.attr('name', $attachments.find('button[data-field-name]').attr('data-field-name') + '[]')
											.val(file_id)
											;
										var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
										var $a = $('<a href="javascript:;"/>')
											.attr('data-context', 'attachment')
											.attr('data-context-id', file_id)
											.text(file_name + ' ' + file_size_label)
											.cerbPeekTrigger()
										;
										var $li = $('<li/>').append($a).append($hidden).append($remove);
										$ul.append($li);
									}
								}

								callback(null);
							}
						};

						xhr.send(f);
					}
				};

				var files = e.originalEvent.clipboardData.files;

				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}

				if(0 === jobs.length)
					return;

				async.parallelLimit(jobs, 2, function(err, json) {
					//if(err)
				});
			});
		});
	}

	// File drag/drop zones
	
	$.fn.cerbAttachmentsDropZone = function() {
		return this.each(function() {
			var $attachments = $(this);
			
			$attachments.on('dragover', function(e) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('dragenter', function(e) {
				$attachments.css('border', '2px dashed rgb(0,120,0)');
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('dragleave', function(e) {
				$attachments.css('border', '');
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
			
			$attachments.on('drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				Devblocks.getSpinner().appendTo($attachments);
				
				$attachments.css('border', '');
				
				// Uploads
				
				var jobs = [];
				var labels = [];
				var values = [];
				
				var uploadFunc = function(f, labels, values, callback) {
					var xhr = new XMLHttpRequest();
					var file = f;
					
					if(xhr.upload) {
						xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
						xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
						xhr.setRequestHeader('X-File-Type', f.type);
						xhr.setRequestHeader('X-File-Size', f.size);
						xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));
						
						xhr.onreadystatechange = function(e) {
							if(xhr.readyState == 4) {
								var json = {};
								if(xhr.status == 200) {
									json = JSON.parse(xhr.responseText);
									labels.push(json.name + ' (' + json.size_label + ')');
									values.push(json.id);
									
								} else {
								}
								
								callback(null, json);
							}
						};
						
						xhr.send(f);
					}
				};
				
				var files = e.originalEvent.dataTransfer.files;
				
				for(var i = 0, f; f = files[i]; i++) {
					jobs.push(
						async.apply(uploadFunc, f, labels, values)
					);
				}
				
				async.series(jobs, function(err, json) {
					var $ul = $attachments.find('ul.chooser-container');
					$attachments.find('.cerb-spinner').first().remove();
					
					for(var i = 0; i < json.length; i++) {
						if(0 == $ul.find('input:hidden[value="' + json[i].id + '"]').length) {
							var $hidden = $('<input type="hidden"/>')
								.attr('name', $attachments.find('button[data-field-name]').attr('data-field-name') + '[]')
								.val(json[i].id)
								;
							var $remove = $('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
							var $a = $('<a href="javascript:;"/>')
								.attr('data-context', 'attachment')
								.attr('data-context-id', json[i].id)
								.text(json[i].name + ' (' + json[i].size_label + ')')
								.cerbPeekTrigger()
								;
							var $li = $('<li/>').append($a).append($hidden).append($remove);
							$ul.append($li);
						}
					}
				});
			});
		});
	}
	
	// Abstract choosers
	
	$.fn.cerbChooserTrigger = function() {
		return this.each(function() {
			var $trigger = $(this);
			var $ul = $trigger.siblings('ul.chooser-container');

			// [TODO] If $ul is null, create it

			$trigger.on('click', function() {
				var context = $trigger.attr('data-context');
				var worklist_columns = $trigger.attr('data-worklist-columns');

				var query = $trigger.attr('data-query');
				var query_req = $trigger.attr('data-query-required');
				var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context);
				
				if(worklist_columns)
					chooser_url += '&worklist[columns]=' + encodeURIComponent(worklist_columns);

				if($trigger.attr('data-single'))
					chooser_url += '&single=1';

				if(typeof query == 'string' && query.length > 0) {
					chooser_url += '&q=' + encodeURIComponent(query);
				}
				
				if(typeof query_req == 'string' && query_req.length > 0) {
					chooser_url += '&qr=' + encodeURIComponent(query_req);
				}
				
				var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');
				
				// [TODO] Trigger open event (if defined)
				
				// [TODO] Bind close event (if defined)
				
				$chooser.one('chooser_save', function(event) {
					// Trigger a selected event
					var evt = $.Event('cerb-chooser-selected', {
						labels: event.labels,
						values: event.values
					});
					$trigger.trigger(evt);
					
					if(typeof event.values == "object" && event.values.length > 0) {
						// Clear previous selections
						if($trigger.attr('data-single'))
							$ul.find('li').remove();
						
						// Check for dupes
						for(var i in event.labels) {
							var evt = $.Event('bubble-create', {
								label: event.labels[i],
								value: event.values[i]
							});
							$ul.trigger(evt);
						}
						
						$trigger.trigger('cerb-chooser-saved');
					}
				});
			});
			
			// Add remove icons with events
			$ul.find('li').each(function() {
				var $li = $(this);
				$('<span class="glyphicons glyphicons-circle-remove"></span>').appendTo($li);
			});
			
			// Abstractly create new bubbles
			$ul.on('bubble-create', function(e) {
				var field_name = $trigger.attr('data-field-name');
				var context = $trigger.attr('data-context');

				e.stopPropagation();
				var $label = e.label;
				var $value = e.value;
				var icon_url = e.icon;
				
				if(undefined !== $label && undefined !== $value) {
					if(0 === $ul.find('input:hidden[value="'+$value+'"]').length) {
						var $li = $('<li/>');
						
						$('<a/>')
							.text($label)
							.attr('href','javascript:;')
							.attr('data-context',context)
							.attr('data-context-id',$value)
							.appendTo($li)
							.cerbPeekTrigger()
							;
						
						if(icon_url && icon_url.length > 0) {
							$('<img class="cerb-avatar">').attr('src',icon_url).prependTo($li);
						}
						
						$('<input type="hidden">').attr('name', field_name).attr('title', $label).attr('value', $value).appendTo($li);
						$('<span class="glyphicons glyphicons-circle-remove"></span>').appendTo($li);
						$ul.append($li);
					}
				}
			});
			
			// Catch bubble remove events at the container
			$ul.on('click','> li span.glyphicons-circle-remove', function(e) {
				e.stopPropagation();
				$(this).closest('li').remove();
				$trigger.trigger('cerb-chooser-saved');
			});
			
			var context = $trigger.attr('data-context');

			// Create
			if($trigger.attr('data-create')) {
				var is_create_ifnull = $trigger.attr('data-create') === 'if-null';
				
				var $button = $('<button type="button"/>')
					.addClass('chooser-create')
					.attr('data-context', context)
					.attr('data-context-id', '0')
					.append($('<span class="glyphicons glyphicons-circle-plus"/>'))
					.insertAfter($trigger)
					;
				
				if($trigger.attr('data-create-defaults')) {
					$button.attr('data-edit', $trigger.attr('data-create-defaults'));
				}
				
				$button.cerbPeekTrigger();
				
				// When the record is saved, retrieve the id+label and make a chooser bubble
				$button.on('cerb-peek-saved', function(e) {
					e.stopPropagation();

					var evt = $.Event('bubble-create');
					evt.label = e.label;
					evt.value = e.id;
					$ul.trigger(evt);
					
					$trigger.trigger('cerb-chooser-saved');
				});
				
				if(is_create_ifnull) {
					if($ul.find('>li').length > 0)
						$button.hide();
					
					$trigger.on('cerb-chooser-saved', function() {
						// If we have zero bubbles, show autocomplete
						if(0 === $ul.find('>li').length) {
							$button.show();
						} else { // otherwise, hide it.
							$button.hide();
						}
					});
				}
			}

			// Autocomplete
			if(undefined !== $trigger.attr('data-autocomplete')) {
				var is_single = $trigger.attr('data-single');
				var placeholder = $trigger.attr('data-placeholder');
				var is_autocomplete_ifnull = $trigger.attr('data-autocomplete-if-empty');
				var autocomplete_placeholders = $trigger.attr('data-autocomplete-placeholders');
				var shortcuts = null == $trigger.attr('data-shortcuts') || 'false' !== $trigger.attr('data-shortcuts');
				
				var $autocomplete = $('<input type="text" size="32">');
				
				if(placeholder)
					$autocomplete.attr('placeholder', placeholder);
				
				$autocomplete.insertAfter($trigger);
				
				$autocomplete.autocomplete({
					delay: 300,
					source: function(request, response) {
						genericAjaxGet(
							'',
							'c=internal&a=invoke&module=records&action=autocomplete&term=' + encodeURIComponent(request.term) + '&context=' + context + '&query=' + encodeURIComponent($trigger.attr('data-autocomplete')),
							function(json) {
								response(json);
							}
						);
					},
					minLength: 1,
					focus:function(event, ui) {
						return false;
					},
					response: function(event, ui) {
						if(!(typeof autocomplete_placeholders == 'string') || 0 === autocomplete_placeholders.length)
							return;
						
						var placeholders = autocomplete_placeholders.split(',');
						
						if(0 === placeholders.length)
							return;
						
						for(var i = 0; i < placeholders.length; i++) {
							var placeholder = $.trim(placeholders[i]);
							ui.content.push({ "label": '(variable) ' + placeholder, "value": placeholder });
						}
					},
					autoFocus:false,
					select:function(event, ui) {
						var $this = $(this);
						
						if($trigger.attr('data-single'))
							$ul.find('li').remove();
						
						var evt = jQuery.Event('bubble-create');
						evt.label = ui.item.label;
						evt.value = ui.item.value;
						
						if(ui.item.icon)
							evt.icon = ui.item.icon;
						
						$ul.trigger(evt);
						
						$trigger.trigger('cerb-chooser-saved');
						
						$this.val('');
						return false;
					}
				});
				
				$autocomplete.autocomplete("instance")._renderItem = function(ul, item) {
					var $div = $("<div/>").css('display', 'flex');
					var $li = $("<li/>").append($div);
					
					var $label = $('<div/>').css('flex','1 1 100%').prependTo($div);
					$('<div/>').text(item.label).css('font-weight','bold').appendTo($label);
					
					if(item.icon) {
						var $icon = $('<div/>').css('flex','1 1 32px').prependTo($div);
						$('<img class="cerb-avatar" style="height:28px;width:28px;border-radius:28px;float:left;margin-right:5px;">').attr('src',item.icon).prependTo($icon);
					}
					
					if(typeof item.meta == 'object') {
						for(var k in item.meta) {
							$('<div/>').append($('<small/>').text(item.meta[k])).appendTo($label);
						}
					}
					
					$li.appendTo(ul);
					return $li;
				};
				
				if(is_autocomplete_ifnull || is_single) {
					if($ul.find('>li').length > 0) {
						$autocomplete.hide();
					}
					
					$trigger.on('cerb-chooser-saved', function() {
						// If we have zero bubbles, show autocomplete
						if(0 === $ul.find('>li').length) {
							$autocomplete.show();
						} else { // otherwise, hide it.
							$autocomplete.hide();
						}
					});
				}
			}
			
			// Show a 'me' shortcut on worker choosers
			if(shortcuts && context === 'cerberusweb.contexts.worker') {
				var $account = $('#lnkSignedIn');
				
				var $button = $('<button type="button"/>')
					.addClass('chooser-shortcut')
					.text('me')
					.click(function() {
						var evt = jQuery.Event('bubble-create');
						evt.label = $account.attr('data-worker-name');
						evt.value = $account.attr('data-worker-id');
						evt.icon = $account.closest('td').find('img:first').attr('src');
						$ul.trigger(evt);
						$trigger.trigger('cerb-chooser-saved');
					})
					.insertAfter($trigger)
					;
				
				if($ul.find('>li').length > 0)
					$button.hide();
				
				$trigger.on('cerb-chooser-saved', function() {
					// If we have zero bubbles, show autocomplete
					if(0 === $ul.find('>li').length) {
						$button.show();
					} else { // otherwise, hide it.
						$button.hide();
					}
				});
			}
			
		});
	}
	
}(jQuery));

// https://github.com/component/textarea-caret-position
(function () {
// We'll copy the properties below into the mirror div.
// Note that some browsers, such as Firefox, do not concatenate properties
// into their shorthand (e.g. padding-top, padding-bottom etc. -> padding),
// so we have to list every single property explicitly.
	var properties = [
		'direction',  // RTL support
		'boxSizing',
		'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
		'height',
		'overflowX',
		'overflowY',  // copy the scrollbar for IE

		'borderTopWidth',
		'borderRightWidth',
		'borderBottomWidth',
		'borderLeftWidth',
		'borderStyle',

		'paddingTop',
		'paddingRight',
		'paddingBottom',
		'paddingLeft',

		// https://developer.mozilla.org/en-US/docs/Web/CSS/font
		'fontStyle',
		'fontVariant',
		'fontWeight',
		'fontStretch',
		'fontSize',
		'fontSizeAdjust',
		'lineHeight',
		'fontFamily',

		'textAlign',
		'textTransform',
		'textIndent',
		'textDecoration',  // might not make a difference, but better be safe

		'letterSpacing',
		'wordSpacing',

		'tabSize',
		'MozTabSize'

	];

	var isBrowser = (typeof window !== 'undefined');
	var isFirefox = (isBrowser && window.mozInnerScreenX != null);

	function getCaretCoordinates(element, position, options) {
		if (!isBrowser) {
			throw new Error('textarea-caret-position#getCaretCoordinates should only be called in a browser');
		}

		var debug = options && options.debug || false;
		if (debug) {
			var el = document.querySelector('#input-textarea-caret-position-mirror-div');
			if (el) el.parentNode.removeChild(el);
		}

		// The mirror div will replicate the textarea's style
		var div = document.createElement('div');
		div.id = 'input-textarea-caret-position-mirror-div';
		document.body.appendChild(div);

		var style = div.style;
		var computed = window.getComputedStyle ? window.getComputedStyle(element) : element.currentStyle;  // currentStyle for IE < 9
		var isInput = element.nodeName === 'INPUT';

		// Default textarea styles
		style.whiteSpace = 'pre-wrap';
		if (!isInput)
			style.wordWrap = 'break-word';  // only for textarea-s

		// Position off-screen
		style.position = 'absolute';  // required to return coordinates properly
		if (!debug)
			style.visibility = 'hidden';  // not 'display: none' because we want rendering

		// Transfer the element's properties to the div
		properties.forEach(function (prop) {
			if (isInput && prop === 'lineHeight') {
				// Special case for <input>s because text is rendered centered and line height may be != height
				if (computed.boxSizing === "border-box") {
					var height = parseInt(computed.height);
					var outerHeight =
						parseInt(computed.paddingTop) +
						parseInt(computed.paddingBottom) +
						parseInt(computed.borderTopWidth) +
						parseInt(computed.borderBottomWidth);
					var targetHeight = outerHeight + parseInt(computed.lineHeight);
					if (height > targetHeight) {
						style.lineHeight = height - outerHeight + "px";
					} else if (height === targetHeight) {
						style.lineHeight = computed.lineHeight;
					} else {
						style.lineHeight = 0;
					}
				} else {
					style.lineHeight = computed.height;
				}
			} else {
				style[prop] = computed[prop];
			}
		});

		if (isFirefox) {
			// Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
			if (element.scrollHeight > parseInt(computed.height))
				style.overflowY = 'scroll';
		} else {
			style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
		}

		div.textContent = element.value.substring(0, position);
		// The second special handling for input type="text" vs textarea:
		// spaces need to be replaced with non-breaking spaces - http://stackoverflow.com/a/13402035/1269037
		if (isInput)
			div.textContent = div.textContent.replace(/\s/g, '\u00a0');

		var span = document.createElement('span');
		// Wrapping must be replicated *exactly*, including when a long word gets
		// onto the next line, with whitespace at the end of the line before (#7).
		// The  *only* reliable way to do that is to copy the *entire* rest of the
		// textarea's content into the <span> created at the caret position.
		// For inputs, just '.' would be enough, but no need to bother.
		span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
		div.appendChild(span);

		var coordinates = {
			top: span.offsetTop + parseInt(computed['borderTopWidth']),
			left: span.offsetLeft + parseInt(computed['borderLeftWidth']),
			height: parseInt(computed['lineHeight'])
		};

		if (debug) {
			span.style.backgroundColor = '#aaa';
		} else {
			document.body.removeChild(div);
		}

		return coordinates;
	}

	if (typeof module != 'undefined' && typeof module.exports != 'undefined') {
		module.exports = getCaretCoordinates;
	} else if(isBrowser) {
		window.getCaretCoordinates = getCaretCoordinates;
	}

}());