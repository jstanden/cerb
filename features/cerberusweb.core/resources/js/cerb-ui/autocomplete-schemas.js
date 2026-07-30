/*
 * cerb-ui editor autocomplete schemas.
 *
 * Shared home for the static suggestion/schema tables consumed by the cerb-ui editor family. Each table
 * lives here once its only consumer is CerbUI:
 *   - `twigAutocompleteSuggestions` — Twig/KataScript snippets/tags/filters/functions, read by
 *     CerbUI.editorCore.kataScript._suggestions() in editor-core.js.
 *   - `cerbAutocompleteSuggestions` — the per-context KATA field-autocomplete schema maps (kataToolbar,
 *     kataSchemaSheet, …), exposed as CerbUI.editorCore.autocompleteSchemas and fed to
 *     CerbUI.KataEditor.kataFieldSource(). editor-core.js attaches it to the namespace (it loads after
 *     this file and reassigns CerbUI.editorCore wholesale, so it can't be attached here).
 * Both are plain module-level bindings (not window properties); reach them via the CerbUI accessors.
 */

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
		{ value: "hash()", snippet: "hash(algo=\"${1:sha256}\", raw=true)", meta: "filter" },
		{ value: "hash_hmac()", snippet: "hash_hmac(\"${1:secret key}\",\"${2:sha256}\")", meta: "filter" },
		{ value: "html_to_text(truncate=50000)", meta: "filter" },
		{ value: "image_info", meta: "filter" },
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
		{ value: "qp_decode", meta: "filter" },
		{ value: "qp_encode", meta: "filter" },
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
		{ value: "sort(func)", snippet: "sort((a,b) => a <=> b)", meta: "filter" },
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
		{ value: "strip_data_uris()", meta: "filter" },
		{ value: "strip_lines(prefixes='>')", meta: "filter" },
		{ value: "strip_pem_blocks()", meta: "filter" },
		{ value: "strip_url_querystrings()", meta: "filter" },
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
		{ value: "xml_encode(format=true)", meta: "filter" },
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
		{ value: "cerb_calendar_get_relative_date(calendar,rel_date,now)", meta: "function" },
		{ value: "cerb_calendar_time_elapsed(calendar,date_from,date_to)", meta: "function" },
		{ value: "cerb_current_worker(expand=[])", meta: "function" },
		{ value: "cerb_extract_mentions(text)", meta: "function" },
		{ value: "cerb_extract_uris(html)", meta: "function" },
		{ value: "cerb_file_url(file_id,full,proxy)", meta: "function" },
		{ value: "cerb_has_priv(priv,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_placeholders_list()", meta: "function" },
		{ value: "cerb_placeholders_list(extract='prefix_')", meta: "function" },
		{ value: "cerb_placeholders_list(extract='prefix_',prefix='new_')", meta: "function" },
		{ value: "cerb_plugin_enabled(plugin_id)", meta: "function" },
		{ value: "cerb_record_readable(record_context,record_id,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_record_writeable(record_context,record_id,actor_context,actor_id)", meta: "function" },
		{ value: "cerb_workflow_config('example.workflow')", meta: "function" },
		{ value: "cerb_workflow_config('example.workflow','key_name')", meta: "function" },
		{ value: "cerb_workflow_resources('example.workflow')", meta: "function" },
		{ value: "cerb_workflow_resources('example.workflow','resource_name')", meta: "function" },
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
		{ value: "uuid()", meta: "function" },
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
		{ value: "xml_xpath_remove(xml,path)", meta: "function" },
		{ value: "xml_tag(xml)", meta: "function" },
	]
};

let cerbAutocompleteSuggestions = {
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
			'llm.agent:',
			'llm.chat:',
			'llm.embed:',
			'llm.router:',
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
		'commands:llm.agent:': [
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:llm.chat:': [
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:llm.embed:': [
			'deny@bool: yes',
			'allow@bool: yes'
		],
		'commands:llm.router:': [
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
		'map:resource:uri:': {
			'type': 'cerb-uri',
			'params': {
				'resource': {
					'types': [
						'cerb.resource.map'
					]
				}
			}
		},

		'map:projection:': [
			'type:',
			'scale:',
			'center:',
			'zoom:'
		],
		'map:projection:type:': [
			'mercator',
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
		'map:regions:properties:join:': [
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
		'map:regions:properties:resource:uri:': {
			'type': 'cerb-uri',
			'params': {
				'resource': {
					'types': [
						'cerb.resource.map.properties'
					]
				}
			}
		},
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
		'map:points:resource:uri:': {
			'type': 'cerb-uri',
			'params': {
				'resource': {
					'types': [
						'cerb.resource.map.points'
					]
				}
			}
		},
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
	kataSchemaMetricsExplorerSeries: {
		'': [
			{
				'caption': 'series:',
				'snippet': 'series/${1:id}:\n  metric: ${2}\n  function: ${3:count}\n  label: ${4}\n'
			}
		],
		'series:': [
			{
				'caption': 'metric:',
				'snippet': 'metric: ${1}'
			},
			{
				'caption': 'function:',
				'snippet': 'function: ${1:count}'
			},
			'label:',
			{
				'caption': 'color:',
				'snippet': 'color: ${1:#1f77b4}'
			},
			{
				'caption': 'type:',
				'snippet': 'type: ${1:line}'
			},
			{
				'caption': 'axis:',
				'snippet': 'axis: ${1:y}'
			},
			{
				'caption': 'stack:',
				'snippet': 'stack: ${1:1}'
			},
			{
				'caption': 'hidden:',
				'snippet': 'hidden@bool: ${1:yes}'
			},
			'filters:'
		],
		'series:metric:': {
			'type': 'metric-names'
		},
		'series:function:': [
			'count',
			'sum',
			'avg',
			'min',
			'max',
			'distinct',
			'faceted_average',
			'faceted_min',
			'faceted_max'
		],
		'series:type:': [
			'line',
			'bar',
			'area'
		],
		'series:axis:': [
			'y',
			'y2'
		],
		'series:stack:': [
			'1', '2', '3', '4', '5', '6', '7', '8', '9'
		],
		'series:hidden:': [
			'yes',
			'no'
		],
		'series:filters:': {
			'type': 'metric-dimensions-series'
		}
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
			'(.*):?menu:icon:': {
				'type': 'icon'
			},
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
		'layout:colors:': [
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
		],
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
				caption: 'code:',
				snippet: 'code/${1:key}:\n  label: ${2:Label}\n  params:\n    syntax: diff\n    #value: literal text\n    #value_key: some_key\n    #value_template@raw: "{{code}}\n'
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
			'text_align: center',
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
			},
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
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
			'text_align: center',
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
			},
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
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
		
		// Code
		'columns:code:': [
			'label:',
			'params:'
		],
		'columns:code:params:': [
			'color@raw:',
			'syntax:',
			'text_color@raw:',
			'text_size@raw: 150%',
			'value:',
			'value_key:',
			'value_template@raw:',
		],
		'columns:code:params:syntax:': [
			'diff',
			'plaintext',
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
			'text_align: center',
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
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
			},
			'color@raw:',
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:icon:params:image:': {
			'type': 'icon'
		},
		'columns:icon:params:image:svg:': [
			'data:',
			'data_key:',
			'data_template@raw:',
		],
		
		// Interaction
		'columns:interaction:': [
			'label:',
			'params:'
		],
		'columns:interaction:params:': [
			'bold@bool: yes',
			'icon:',
			'inputs:',
			'text:',
			'text_key:',
			'text_template@raw:',
			'uri:',
			'uri_key:',
			'uri_template@raw:',
			'color@raw:',
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		'columns:interaction:params:icon:': [
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			},
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
			}
		],
		'columns:interaction:params:icon:image:': {
			'type': 'icon'
		},
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
			},
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
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
			'text_align: center',
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
			'icon:',
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%',
			'underline@bool:',
		],
		'columns:search:params:bold:': [
			'yes',
			'no'
		],
		'columns:search:params:icon:': [
			'image:',
			'image_key:',
			'image_template@raw:',
			{
				'caption': 'record_uri:',
				'snippet': 'record_uri@raw: cerb:${1:record_type}:${2:record_id}'
			},
			{
				'caption': 'svg:',
				'snippet': "svg:\n  data: ${1:<svg></svg>}"
			}
		],
		'columns:search:params:icon:image:': {
			'type': 'icon'
		},
		
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
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%'
		],
		
		// Selection
		'columns:selection:': [
			'params:'
		],
		'columns:selection:params:': [
			'mode:',
			'value:',
			'value_key:',
			'value_template@raw: {{id}}',
			'color@raw:',
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%',
			'selectable@raw: {{expression}}'
		],
		'columns:selection:params:mode:': [
			'single',
			'multiple'
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
			'show_labels@bool: yes',
			'text_align: center',
			'text_color@raw:',
			'text_size@raw: 150%',
			{
				'caption': 'threshold_colors:',
				'snippet': 'threshold_colors:\n  0: rgb(230,70,70)\n  50: rgb(175,175,175)\n  51: rgb(0,200,0)'
			},
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
			'text_align: center',
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
			'kata:',
			'text_size@raw: 150%'
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
			'columns:toolbar:params:kata:(.*):?menu:icon:': {
				'type': 'icon'
			},
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
