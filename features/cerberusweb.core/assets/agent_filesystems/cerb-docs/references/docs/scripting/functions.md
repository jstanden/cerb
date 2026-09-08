---
id: "docs-scripting-functions"
title: "Scripting Reference: Functions"
url: "https://cerb.ai/docs/scripting/functions/"
summary: "This webpage serves as a comprehensive scripting reference for functions available in Cerb's automation scripting and snippets. It details a wide array of functions, including those for manipulating arrays (e.g., array_column, array_diff, array_sort_keys), handling JSON and XML data (e.g., json_decode, xml_decode, xml_xpath), and performing various utility operations (e.g., random_string, validate_email, clamp_int). Additionally, it covers Cerb-specific functions for automation, calendar management, and permissions (e.g., cerb_automation, cerb_calendar_get_relative_date, cerb_has_priv). Each function is explained with examples, showcasing its syntax and potential use cases, making this page a valuable resource for developers working with Cerb's scripting capabilities."
tags: ["docs", "docs-scripting"]
---
These functions are available in automation scripting and snippets:

- [array\_column](#array_column)
- [array\_combine](#array_combine)
- [array\_count\_values](#array_count_values)
- [array\_diff](#array_diff)
- [array\_extract\_keys](#array_extract_keys)
- [array\_fill\_keys](#array_fill_keys)
- [array\_intersect](#array_intersect)
- [array\_matches](#array_matches)
- [array\_sort\_keys](#array_sort_keys)
- [array\_sum](#array_sum)
- [array\_unique](#array_unique)
- [array\_values](#array_values)
- [attribute](#attribute)
- [cerb\_automation](#cerb_automation)
- [cerb\_avatar\_image](#cerb_avatar_image)
- [cerb\_avatar\_url](#cerb_avatar_url)
- [cerb\_calendar\_get\_relative\_date](#cerb_calendar_get_relative_date)
- [cerb\_calendar\_time\_elapsed](#cerb_calendar_time_elapsed)
- [cerb\_current\_worker](#cerb_current_worker)
- [cerb\_extract\_mentions](#cerb_extract_mentions)
- [cerb\_extract\_uris](#cerb_extract_uris)
- [cerb\_file\_url](#cerb_file_url)
- [cerb\_has\_priv](#cerb_has_priv)
- [cerb\_placeholders\_list](#cerb_placeholders_list)
- [cerb\_plugin\_enabled](#cerb_plugin_enabled)
- [cerb\_record\_readable](#cerb_record_readable)
- [cerb\_record\_writeable](#cerb_record_writeable)
- [cerb\_url](#cerb_url)
- [cerb\_workflow\_config](#cerb_workflow_config)
- [cerb\_workflow\_resources](#cerb_workflow_resources)
- [clamp\_float](#clamp_float)
- [clamp\_int](#clamp_int)
- [cycle](#cycle)
- [date](#date)
- [date\_lerp](#date_lerp)
- [dict\_set](#dict_set)
- [dict\_unset](#dict_unset)
- [dns\_get\_record](#dns_get_record)
- [dns\_host\_by\_ip](#dns_host_by_ip)
- [json\_decode](#json_decode)
- [jsonpath\_set](#jsonpath_set)
- [kata\_parse](#kata_parse)
- [max](#max)
- [min](#min)
- [placeholders\_list](#placeholders_list)
- [random](#random)
- [random\_string](#random_string)
- [range](#range)
- [regexp\_match\_all](#regexp_match_all)
- [shuffle](#shuffle)
- [uuid](#uuid)
- [validate\_email](#validate_email)
- [validate\_number](#validate_number)
- [vobject\_parse](#vobject_parse)
- [xml\_attr](#xml_attr)
- [xml\_attrs](#xml_attrs)
- [xml\_decode](#xml_decode)
- [xml\_encode](#xml_encode)
- [xml\_tag](#xml_tag)
- [xml\_xpath](#xml_xpath)
- [xml\_xpath\_ns](#xml_xpath_ns)
- [xml\_xpath\_remove](#xml_xpath_remove)
- [References](#references)

https://www.youtube.com/embed/S-EI-uMJZx0

## array\_column

The **array\_column** function extracts a column from the elements of an array:

```
{% set people = [
	{"id": 1, "name": "Kina Halpue", "email": "kina@cerb.example"},
	{"id": 2, "name": "Milo Dade", "email": "milo@cerb.example"},
	{"id": 3, "name": "Janey Youve", "email": "janey@cerb.example"},
] %}
The email addresses are: {{array_column(people,'email')|join(', ')}}
```

```
The email addresses are: kina@cerb.example, milo@cerb.example, janey@cerb.example
```

## array\_combine

The **array\_combine** function creates a new array with the given `keys` and `values`:

```
{% set keys = ['name', 'age', 'email'] %}
{% set values = ['Janey Youve', '30-ish', 'janey@cerb.example'] %}
{% set person = array_combine(keys, values) %}
{{person.name}} can be reached at {{person.email}}
```

```
Janey Youve can be reached at janey@cerb.example
```

## array\_count\_values

The **array\_count\_values** function takes an array of values as input, and returns an array with distinct values as keys and their count of occurrences. This function only works on arrays of strings or numbers.

```
{% set values = [1,2,3,1,3,2,3,1,2,1,3,1,3] %}
{{array_count_values(values)|json_encode|json_pretty}}
```

```
{
    "1": 5,
    "2": 3,
    "3": 5
}
```

## array\_diff

The **array\_diff** function returns the items in the second array that are not present in the first array:

```
{% set arr1 = ['Apple', 'Google', 'Microsoft'] %}
{% set arr2 = ['Apple', 'Microsoft', 'Cerb'] %}
{% set diff = array_diff(arr2, arr1) %}
These are new: {{diff|join(', ')}}
```

```
These are new: Cerb
```

## array\_extract\_keys

Returns the given keys from all elements of a list.

```
{% set records = [
	{
		id: 1,
		subject: "Help with the API",
		status: "open",
		sender: "customer@cerb.example",
	},
	{
		id: 2,
		subject: "Automating email replies",
		status: "open",
		sender: "customer@cerb.example",
	}
] %}
Sender,Subject,Status
{{array_extract_keys(records, ['sender','subject','status'])|csv}}
```

```
Sender,Subject,Status
customer@cerb.example,"Help with the API",open
customer@cerb.example,"Automating email replies",open
```

## array\_fill\_keys

Create an array with the given keys, each set to the default value.

`array_fill_keys(keys,value)`

```
{{array_fill_keys(range(1,10),true)|json_encode}}
```

```
{"1":true,"2":true,"3":true,"4":true,"5":true,"6":true,"7":true,"8":true,"9":true,"10":true}
```

## array\_intersect

Returns a new array for all the elements in array1 that are also present in array2. This is the opposite of [array\_diff](#array_diff).

```
{% set arr1 = ['Apple', 'Google', 'Microsoft'] %}
{% set arr2 = ['Apple', 'Microsoft', 'Cerb'] %}
{% set intersect = array_intersect(arr2, arr1) %}
These are in both: {{intersect|join(', ')}}
```

```
These are in both: Apple, Microsoft
```

## array\_matches

Compares an array of values to an array of patterns.

```
{% set recipients = ['support@cerb.example','sales@cerb.example'] %}
{% set patterns = ['sales@*'] %}
{% set results = array_matches(recipients, patterns) %}
Matches: {{results|join(', ')}}
```

```
Matches: sales@cerb.example
```

## array\_sort\_keys

Sort an associative array by its keys rather than its values.

```
{% set arr = {"z":"A", "a":"B", "m":"C"} %}
{% set arr = array_sort_keys(arr) %}
{{arr|keys|join(',')}}
```

```
a,m,z
```

## array\_sum

Sum the numeric elements of an array.

```
{{array_sum([1,2,3,4,5])}}
```

```
15
```

## array\_unique

Return a new array with only the distinct values from the `array` argument.

```
{% set arr = [1,1,2,2,3,3,4,4,5,5,6] %}
Unique values {{array_unique(arr)|join(',')}}
```

```
Unique values 1,2,3,4,5,6
```

## array\_values

Return the values from an associative array as a new indexed array. For instance, this can affect the output in JSON encoding by using `[]` rather than `{key:value}`.

```
{% set arr = {"z":"A", "a":"B", "m":"C"} %}
{{array_values(arr)|json_encode}}
```

```
["A","B","C"]
```

## attribute

Access the values of an object with a variable key:

```
{% set person = {"first_name": "Kina", "last_name": "Halpue", "title": "Customer Support Supervisor"} %}
{% set key = 'title' %}
{{attribute(person, key)}}
```

```
Customer Support Supervisor
```

## cerb\_automation

Invoke a [scripting.function](/docs/automations/triggers/scripting.function/) automation from any feature that supports [scripting](/docs/scripting/).

The function returns keys for `exit_state:` (`exit`, `return`, `error`) and `return:` (an arbitrary dictionary).

This brings the full functionality of automations to email signatures, snippets, legacy bot behaviors, automation event bindings, toolbars bindings, etc.

For instance, a snippet could use an automation to dynamically generate content based on the target record or current worker. This solves many feature requests.

`cerb_automation(uri, inputs)`

| **uri** | The URI of an [automation](/docs/automations/) record to invoke. It must be of type `scripting.function`. |
| **inputs** | A key/value dictionary of inputs. The possible keys depend on the function being invoked. |

```
{% set ip_data = cerb_automation('wgm.scripting.getLocationByIP', { ip:"1.2.3.4" } ) %}
{% if ip_data.return.data %}
I see you are contacting us from {{ip_data.return.data.country_name}}.
{% endif %}
```

```
I see you are contacting us from Australia.
```

## cerb\_avatar\_image

Retrieve the avatar image for a given record type and ID.

`cerb_avatar_image(record_type, id, updated)`

```
{{cerb_avatar_image('worker','1','now'|date('U'))}}
```

```
<img src="https:/cerb.example/avatars/worker/1?v=1513212603" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;">
```

## cerb\_avatar\_url

Retrieve the avatar image URL for a given record type and ID.

`cerb_avatar_url(record_type, id, updated)`

```
{{cerb_avatar_url('worker','1','now'|date('U'))}}
```

```
https://cerb.example/avatars/worker/1?v=1513212702
```

## cerb\_calendar\_get\_relative\_date

Calculate a future timestamp using calendar availability. For instance, this can be used for SLAs to generate a due date like "+4 business hours".

`cerb_calendar_get_relative_date(calendar,rel_date,now)`

| **calendar** | The ID of the [calendar](/docs/records/types/calendar/) to use for determining availability. |
| **date\_rel** | The time increment (e.g. "+2 hours"). |
| **now** | An optional starting date/time. |

```
Now: {{"now"|date('r')}}
Due: {{cerb_calendar_get_relative_date(123,'+2 hours')|date('r')}}
```

```
Now: Fri, 18 Oct 2024 20:02:18 -0700
Due: Mon, 21 Oct 2024 09:00:00 -0700
```

## cerb\_calendar\_time\_elapsed

Calculate the time elapsed (in seconds) between two dates using calendar availability.

`cerb_calendar_time_elapsed(calendar,date_from,date_to)`

| **calendar** | The ID of the [calendar](/docs/records/types/calendar/) to use for determining availability. |
| **date\_from** | The starting date/time. |
| **date\_to** | The ending date/time. |

```
{{cerb_calendar_time_elapsed(123,'last Friday 5pm','now')|secs_pretty}}
```

```
18 hours, 13 mins
```

## cerb\_current\_worker

Return a dictionary for the currently logged in worker. This returns an empty dictionary when used outside a browser session.

`cerb_current_worker(expand)`

| **expand** | An optional comma-delimited string or array of dictionary keys to expand. |

```
Hello {{cerb_current_worker().first_name}}!
```

```
Hello Kina!
```

## cerb\_extract\_mentions

Return the workers named by `@mention` in a block of text.

`cerb_extract_mentions(text)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `text` | Plain text to scan. Not HTML. |

**Returns:** A plain list of worker [dictionaries](/docs/guide/developers/dictionaries/), one per matched worker. Empty when nothing matches.

Each element is a full worker dictionary, so any [worker placeholder](/docs/records/types/worker/) can be read from it.

```
{% set mentioned = cerb_extract_mentions("Paging @kina for review") %}
{% for worker in mentioned %}
{{worker.full_name}} -- {{worker.title}}
{% endfor %}
```

## cerb\_extract\_uris

Return an array of URLs found in HTML content, along with metadata (e.g. tag, attributes, URI parts).

In the response, URLs are replaced with `tokens` in the `template` which can be modified with the [|replace](/docs/scripting/filters/#replace) filter.

For instance, this function can be used to rewrite all links in an email template for click tracking.

`cerb_extract_uris(html)`

| **html** | The HTML content to extract links from. |

```
{% set html %}
This is some <b>HTML</b> with <a href="https://cerb.ai/">links</a>.
{% endset %}
{% set results = cerb_extract_uris(html) %}
{{results|json_encode|json_pretty}}
```

```
{
    "tokens": {
        "#uri-61411f091662a": "https://cerb.ai/"
    },
    "context": {
        "#uri-61411f091662a": {
            "is_tag": true,
            "name": "a",
            "attr": "href",
            "attrs": {
                "href": "https://cerb.ai/"
            },
            "uri_parts": {
                "scheme": "https",
                "userinfo": null,
                "host": "cerb.ai",
                "port": null,
                "path": "/",
                "query": null,
                "fragment": null
            }
        }
    },
    "template": "This is some <b>HTML</b> with <a href=\"#uri-61411f091662a\">links</a>.\n"
}
```

To rewrite links:

```
{% set html %}
This is some <b>HTML</b> with <a href="https://cerb.ai/">links</a>.
{% endset %}
{% set results = cerb_extract_uris(html) %}
{% set new_urls = results.tokens|map(
  (url,token) => "https://proxy.example/click?url=" ~ url|url_encode
)%}
{{results.template|replace(new_urls)}}
```

```
This is some <b>HTML</b> with <a href="https://proxy.example/click?url=https%3A%2F%2Fcerb.ai%2F">links</a>.
```

## cerb\_file\_url

Retrieve the download link for a given attachment ID.

This automatically adapts to use within Cerb and community portals (e.g. SSL, proxies).

`cerb_file_url(id)`

```
{{cerb_file_url('1')}}
```

```
https://cerb.example/files/1/original_message.html
```

## cerb\_has\_priv

Returns a boolean depending on whether the given actor has the given privilege among their roles. If no actor is given, the current worker is assumed. This allows bot functionality, snippets, and widgets, to adapt based on worker permissions. This is particularly useful in HTML-based profile widgets.

```
{% if cerb_has_priv('contexts.cerberusweb.context.ticket.create', 'worker', 1) %}
Worker #1 has permission to create tickets.
{% endif %}
```

```
Worker #1 has permission to create tickets.
```

## cerb\_placeholders\_list

Return an [object](/docs/scripting/arrays-objects/) with every placeholder in the current behavior.

`cerb_placeholders_list(extract, prefix)`

| **extract** | The key prefix to extract (e.g. `ticket_group_`) |
| **prefix** | The optional new prefix to add (e.g. `group_`) |

```
{{cerb_placeholders_list()|json_encode|json_pretty}}
```

```
{
    "worker__context": "cerberusweb.contexts.worker",
    "worker__loaded": true,
    "worker__label": "Kina Halpue",
    "worker__image_url": "https://cerb.example/avatars/worker/1?v=1512582324",
    "worker_at_mention_name": "Kina",
    "worker_calendar_id": 7,
    "worker_dob": null,
    "worker_id": 1,
    "worker_first_name": "Kina",
    "worker_full_name": "Kina Halpue",
    "worker_gender": "F",
    "worker_is_disabled": 0,
    "worker_is_superuser": 1,
    "worker_language": "en_US",
    "worker_last_name": "Halpue",
    "worker_location": "",
    "worker_mobile": "15555555555",
    "worker_phone": "",
    "worker_time_format": "D, d M Y h:i a",
    "worker_timezone": "America/Los_Angeles",
    "worker_title": "Customer Support",
    "worker_updated": 1512582324,
    "worker_record_url": "https://cerb.example/profiles/worker/1-Kina-Halpue",
    ...
}
```

## cerb\_plugin\_enabled

Test if a Cerb plugin is installed and enabled.

For instance, this can be used to make dashboard tabs or widgets conditional on a particular plugin being enabled (e.g. project boards).

`cerb_plugin_enabled(plugin_id)`

| **plugin\_id** | The name or ID of the [workflow](/docs/workflows/). |

```
{{cerb_plugin_enabled('cerb.classifiers')}}
```

```
1
```

## cerb\_record\_readable

Returns a boolean if the given actor has read access to the given record. If no actor is provided then the current worker is assumed. This allows bots and widgets to adapt based on record permissions. For instance, an HTML widget on a profile dashboard could only show a button to workers who can modify the record.

```
{% if cerb_record_readable('ticket', 123, 'worker', 1) %}
Worker #1 can read ticket #123.
{% endif %}
```

```
Worker #1 can read ticket #123.
```

## cerb\_record\_writeable

Returns a boolean if the given actor has write access to the given record. If no actor is provided then the current worker is assumed. This allows bots and widgets to adapt based on record permissions. For instance, an HTML widget on a profile dashboard could only show a button to workers who can modify the record.

```
{% if cerb_record_writeable('ticket', 123, 'worker', 1) %}
Worker #1 can modify ticket #123.
{% endif %}
```

```
Worker #1 can modify ticket #123.
```

## cerb\_url

Retrieve a full URL to a page or resource in Cerb.

This automatically adapts to use within Cerb and community portals (e.g. SSL, proxies).

```
{{cerb_url("c=profiles&type=ticket&id=5")}}
```

```
https://cerb.example/profiles/ticket/5
```

## cerb\_workflow\_config

Perform runtime configuration lookups from any feature that supports automation scripting (e.g. automations, workflows, snippets). For instance, you can create a workflow just for sharing values (e.g. API keys) between multiple workflows.

`cerb_workflow_config(name_or_id,key,default)`

| **name\_or\_id** | The name or ID of the [workflow](/docs/workflows/). |
| **key** | The optional config key to return. If omitted, all keys/values are returned as a map. |
| **default** | The optional default value if the key doesn't exist. |

```
{{cerb_workflow_config('example.workflow','secretCode',null)}}
```

```
sup3rs3cr3t
```

## cerb\_workflow\_resources

Perform runtime resource lookups and return a map of workflow resources and their local record IDs. This is useful from automations, event listeners, and toolbars.

`cerb_workflow_resources(name_or_id)`

| **name\_or\_id** | The name or ID of the [workflow](/docs/workflows/). |

```
{{cerb_workflow_resources('example.workflow'|json_encode}}
```

```
{"records":{"automation/example":123}}
```

## clamp\_float

Set the range boundaries for a decimal value.

```
{{clamp_float(-105.19,0,100)}}
```

```
0
```

## clamp\_int

Set the range boundaries for an integer value.

```
{{clamp_int(110,-90,90)}}
```

```
90
```

## cycle

Round-robin through a sequence.

```
{% set options = ['odd','even'] %}
{% for n in 1..10 %}
* {{cycle(options, n)}}
{% endfor %}
```

```
* even
* odd
* even
* odd
* even
* odd
* even
* odd
* even
* odd
```

## date

Create a date object for use with the [date\_modify](/docs/scripting/filters/#date_modify) filter.

```
{% set d = date('1-Jan-2018 10:00am') %}
{{d|date_modify('+2 hours')|date('F d, Y g:ia')}}
```

```
January 01, 2018 12:00pm
```

## date\_lerp

Interpolate the timestamps between two dates with the given `unit` and `step`.

`date_lerp(date_range,unit,step,limit)`

**Arguments:**

| Name | Notes |
| --- | --- |
| **date\_range** | An absolute range like `2023-01-01 to 2023-12-31`, a relative range like `-7 days to now`, or a shortcut like `this month`. |
| **unit** | `minute`, `hour`, `day`, `week`, `month`, `year` |
| **step** | The number of `unit` to increment (e.g. `5`). Default `1`. |
| **limit** | The maximum number of results. Default `10000`. |

**Returns:** An array of Unix timestamps.

```
{{date_lerp('this month',unit='day',step=5)|map((v) => v|date('r'))|json_encode|json_pretty}}
```

```
[
    "Sat, 01 Oct 2022 00:00:00 -0700",
    "Thu, 06 Oct 2022 00:00:00 -0700",
    "Tue, 11 Oct 2022 00:00:00 -0700",
    "Sun, 16 Oct 2022 00:00:00 -0700",
    "Fri, 21 Oct 2022 00:00:00 -0700",
    "Wed, 26 Oct 2022 00:00:00 -0700",
    "Mon, 31 Oct 2022 00:00:00 -0700"
]
```

## dict\_set

You can use the **dict\_set** function to quickly add, modify, or append items in an array or object.

`dict_set(object,path,value,delimiter) : object`

**Arguments:**

| Name | Notes |
| --- | --- |
| **object** | The object to modify |
| **path** | The key or key path (with delimiters) to set |
| **value** | The new value for the given key or key path |
| **delimiter** | Defaults to dot (`.`), but may be any character sequence (e.g. `||`) |

**Returns:** The function returns a modified version of `object`.

You can set deeply nested keys in a single line using dot-notation:

```
{% set var = {"group": {}} %}
{% set var = dict_set(var, 'group.name', 'Support') %}
{% set var = dict_set(var, 'group.manager.name.first', 'Kina') %}
{% set var = dict_set(var, 'group.manager.name.last', 'Halpue') %}
{{var|json_encode|json_pretty}}
```

```
{
    "group": {
        "name": "Support",
        "manager": {
            "name": {
                "first": "Kina",
                "last": "Halpue"
            }
        }
    }
}
```

Append items to an array by adding `.[]` to the key:

```
{% set var = {"group": {}} %}
{% set var = dict_set(var, 'group.name', 'Support') %}
{% set var = dict_set(var, 'group.members.[]', 'Kina Halpue') %}
{% set var = dict_set(var, 'group.members.[]', 'William Portcullis') %}
{% set var = dict_set(var, 'group.members.[]', 'Steven Emplois') %}
{{var|json_encode|json_pretty}}
```

```
{
    "group": {
        "name": "Support",
        "members": [
            "Kina Halpue",
            "William Portcullis",
            "Steven Emplois"
        ]
    }
}
```

Append to nested arrays:

```
{% set var = [1,2,[3,4,[5,6]]] %}
{% set var = dict_set(var, '2.2.[]', 7) %}
{% set var = dict_set(var, '2.2.[]', 8) %}
{% set var = dict_set(var, '2.3', 9) %}
{{var|json_encode|json_pretty}}
```

```
[
  1,
  2,
  [
    3,
    4,
    [
      5,
      6,
      7,
      8
    ],
    9
  ]
]
```

## dict\_unset

You can use the **dict\_unset** function to remove items by key from an array or object.

You can unset deeply nested keys in a single line using dot-notation:

```
{% set person = {"person":{"name":{"first":"Jane","last":"Tester"},"age":28,"location":"Secret"}} %}
{% set person = dict_unset(person, ['person.name.last','person.age','person.location']) %}
{{person|json_encode|json_pretty}}
```

```
{
    "person": {
        "name": {
            "first": "Jane"
        }
    }
}
```

## dns\_get\_record

Resolve DNS records by hostname and type. This enables workflows like verifying domain ownership via TXT records, validating SPF/DKIM, verifying MX servers, etc.

`dns_get_record(hostname,type)`

- **hostname**: The lookup hostname.
- **type**: The record type (`a`, `aaaa`, `caa`, `cname`, `mx`, `ns`, `ptr`, `soa`, `srv`, `txt`)

```
{{dns_get_record('cerb.ai','a')|json_encode|json_pretty}}
```

```
[
    {
        "host": "cerb.ai",
        "class": "IN",
        "ttl": 77,
        "type": "A",
        "ip": "54.192.81.51"
    },
    {
        "host": "cerb.ai",
        "class": "IN",
        "ttl": 77,
        "type": "A",
        "ip": "54.192.81.69"
    }
]
```

## dns\_host\_by\_ip

Resolve a hostname from an IP. If a name can't be resolved for a valid IP, the IP is returned. If an invalid IP is provided, the result is an empty string.

`dns_host_by_ip(ip)`

- **ip**: The IP address to reverse lookup a hostname.

```
{{dns_host_by_ip('54.148.127.4')}}
```

```
cerb.email
```

## json\_decode

You can decode a JSON-encoded string with the **json\_decode** function:

```
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
Customer: {{json.name}}
Order #: {{json.order_id}}
```

```
Customer: Joe Customer
Order #: 12345
```

This returns an [object](/docs/scripting/arrays-objects/).

## jsonpath\_set

This is nearly identical to [dict\_set](#dict_set).

```
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
{% set json = jsonpath_set(json, 'order_id', '67890') %}
{{json.order_id}}
```

```
67890
```

You can specify an array by appending `[]` without a leading dot (`.`):

```
{% set json_string = "{\"team\":{\"groups\":[]}}" %}
{% set json = json_decode(json_string) %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Support') %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Sales') %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Development') %}
{{json|json_encode|json_pretty}}
```

```
{
    "team": {
        "groups": [
            "Support",
            "Sales",
            "Development"
        ]
    }
}
```

## kata\_parse

Parses a KATA text block into an object.

```
{% set kata %}
colors@list:
  red
  green
  blue
size@int: 100
{% endset %}
{{kata_parse(kata)|json_encode|json_pretty}}
```

```
{
    "colors@list": "red\ngreen\nblue",
    "size@int": "100"
}
```

## max

Return the largest value in an array or object.

```
{% set numbers = [1,9,8,4,2] %}
{{max(numbers)}}
```

```
9
```

## min

Return the smallest value in an array or object.

```
{% set numbers = [1,9,8,4,2] %}
{{min(numbers)}}
```

```
1
```

## placeholders\_list

An alias for [cerb\_placeholders\_list](#cerb_placeholders_list), with identical behavior.

Prefer `cerb_placeholders_list`, which matches the rest of the `cerb_` family.

## random

Return a random item from a string or array, or a random number between 0 and the given number (inclusive).

```
{{random([1,2,3,4,5,6,7,8,9,0])}}
{{random("abcdefghijklmnopqrstuvwxyz")}}
{{random(20)}}
```

```
9
o
17
```

## random\_string

Generate a random string of the given length. This is useful for generating confirmation codes or temporary passwords.

```
{{random_string(16)}}
```

```
61AE3XG3ZMW8QDTM
```

## range

Return an array with values between `from` and `to` (inclusive).

`range(from,to,step)`

```
{{range(5,15)|json_encode}}
{{range(5,15,2)|json_encode}}
```

```
[5,6,7,8,9,10,11,12,13,14,15]
[5,7,9,11,13,15]
```

## regexp\_match\_all

`regexp_match_all(pattern, string, group)`

```
{% set headers = 
"X-Mailer: Cerb
From: customer@cerb.example
To: support@cerb.example
"
%}
{% set results = regexp_match_all("#^(.*?): (.*?)$#m", headers) %}
{{results|json_encode|json_pretty}}
```

```
[
  [
    "X-Mailer: Cerb",
    "From: customer@cerb.example",
    "To: support@cerb.example"
  ],
  [
    "X-Mailer",
    "From",
    "To"
  ],
  [
    "Cerb",
    "customer@cerb.example",
    "support@cerb.example"
  ]
]
```

## shuffle

Randomize an array:

```
{{shuffle([1,2,3,4,5])|json_encode}}
```

```
[2,4,5,1,3]
```

## uuid

Generate a UUID (version 1).

`uuid()`

```
{{uuid()}}
```

```
5f9d7c1e-3b2a-11f0-9c1d-0242ac120002
```

This is useful for minting a stable identifier within a script. For instance, an [agent](/docs/agents/) session ID shared between [`llm.agent:`](/docs/automations/commands/llm.agent/#session_id), an [`agentPrompt`](/docs/automations/triggers/interaction.worker/elements/agentPrompt/) composer, and an [`llmTranscript`](/docs/automations/triggers/interaction.worker/elements/llmTranscript/) element:

```
set:
  session_id: {{uuid()}}
```

## validate\_email

Validate an email address:

```
{{validate_email('kina')|json_encode}}
{{validate_email('kina#cerb.example')|json_encode}}
{{validate_email('kina@cerb.example')|json_encode}}
```

```
false
false
true
```

## validate\_number

Validate a number:

```
{{validate_number('abcde')|json_encode}}
{{validate_number('20.f')|json_encode}}
{{validate_number(10)|json_encode}}
{{validate_number('123.45')|json_encode}}
```

```
false
false
true
true
```

## vobject\_parse

Parse a block of text in VObject format (e.g. vCard, iCal).

`vobject_parse(text)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `text` | The VOBJECT text to parse |

**Returns:** An object with properties and parameters.

```
{% set vcard %}
begin:vcard
source:ldap://cn=Meister%20Berger,o=Universitaet%20Goerlitz,c=DE
name:Meister Berger
fn:Meister Berger
n:Berger;Meister
bday;value=date:1963-09-21
o:Universit=E6t G=F6rlitz
title:Mayor
title;language=de;value=text:Burgermeister
note:The Mayor of the great city of
  Goerlitz in the great country of Germany.
email;internet:mb@goerlitz.de
home.tel;type=fax,voice,msg:+49 3581 123456
home.label:Hufenshlagel 1234\n
 02828 Goerlitz\n
 Deutschland
end:vcard
{% endset %}
{{vobject_parse(vcard)|json_encode|json_pretty}}
```

```
{
    "VCARD": [
        {
            "props": {
                "SOURCE": [
                    {
                        "params": [],
                        "value": "ldap://cn=Meister%20Berger,o=Universitaet%20Goerlitz,c=DE"
                    }
                ],
                "NAME": [
                    {
                        "params": [],
                        "value": "Meister Berger"
                    }
                ],
                "FN": [
                    {
                        "params": [],
                        "value": "Meister Berger"
                    }
                ],
                "N": [
                    {
                        "params": [],
                        "value": "Berger;Meister"
                    }
                ],
                "BDAY": [
                    {
                        "params": {
                            "value": "date"
                        },
                        "value": "1963-09-21"
                    }
                ],
                "O": [
                    {
                        "params": [],
                        "value": "Universit=E6t G=F6rlitz"
                    }
                ],
                "TITLE": [
                    {
                        "params": [],
                        "value": "Mayor"
                    },
                    {
                        "params": {
                            "language": "de",
                            "value": "text"
                        },
                        "value": "Burgermeister"
                    }
                ],
                "NOTE": [
                    {
                        "params": [],
                        "value": "The Mayor of the great city of Goerlitz in the great country of Germany."
                    }
                ],
                "EMAIL": [
                    {
                        "params": {
                            "internet": ""
                        },
                        "value": "mb@goerlitz.de"
                    }
                ],
                "HOME.TEL": [
                    {
                        "params": {
                            "type": "fax,voice,msg"
                        },
                        "value": "+49 3581 123456"
                    }
                ],
                "HOME.LABEL": [
                    {
                        "params": [],
                        "value": "Hufenshlagel 1234\n02828 Goerlitz\nDeutschland"
                    }
                ]
            }
        }
    ]
}
```

## xml\_attr

Return a single attribute from an XML node.

`xml_attr(xml_node, attr, default)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | A single XML node, usually from [xml\_xpath](#xml_xpath) |
| `attr` | The name of an attribute |
| `default` | Optional. Returned when the attribute is absent. Defaults to `null`. |

**Returns:** The attribute's value, or `default` when the attribute is absent (`null` if no default was given). Returns `false` when `xml_node` isn't an XML node.

An attribute that is present but empty returns an empty string, not the default.

```
{% set xml_string %}
<?xml version = "1.0" encoding = "UTF-8"?>
<Movies>
    <Movie rating="R">
        <Title runtime="142">The Shawshank Redemption</Title>
        <Genre>Drama</Genre>
        <Director>
            <Name highratedmovie="The Mist">
                <First>Frank</First>
                <Last>Darabont</Last>
            </Name>
        </Director>
        <Studio>Columbia Pictures</Studio>
        <Year>1994</Year>
    </Movie>
</Movies>
{% endset %}
{% set xml = xml_decode(xml_string) %}
{% set movie = xml_xpath(xml, '//Movie')|first %}
{% set runtime = xml_attr(movie.Title,'runtime') %}
The runtime of {{movie.Title}} is {{runtime ? (60*runtime)|secs_pretty : 'unknown'}}.
```

```
The runtime of The Shawshank Redemption is 2 hours, 22 mins.
```

## xml\_attrs

Return all attributes from an XML node.

`xml_attrs(xml_node)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | A single XML node, usually from [xml\_xpath](#xml_xpath) |

**Returns:** An array of attribute keys and values.

```
{% set xml_string %}
<?xml version = "1.0" encoding = "UTF-8"?>
<Movies>
    <Movie rating="R">
        <Title runtime="177">The Godfather</Title>
        <Genre> Crime Drama </Genre>
        <Director>
            <Name>
                <First>Francis Ford</First>
                <Last>Coppola</Last>
            </Name>
        </Director>
        <Studio>Paramount Pictures</Studio>
        <Year>1972</Year>
    </Movie>
    <Movie rating= "R">
        <Title runtime="142">The Shawshank Redemption</Title>
        <Genre>Drama</Genre>
        <Director>
            <Name highratedmovie="The Mist">
                <First>Frank</First>
                <Last>Darabont</Last>
            </Name>
        </Director>
        <Studio>Columbia Pictures</Studio>
        <Year>1994</Year>
    </Movie>
</Movies>
{% endset %}
{% set xml = xml_decode(xml_string) %}
{% set movies = xml_xpath(xml, '//Movie') %}
{{xml_attrs(movies[1])|json_encode|json_pretty}}
```

```
{
    "rating": "R"
}
```

## xml\_decode

You can decode an XML[1](#fn:xml) string into an XML object with the **xml\_decode** function.

Use the [xml\_xpath](#xml_xpath) function to extract values with XPath[2](#fn:xpath) queries.

`xml_decode(xml_string,namespaces,mode)`

- **xml\_string**: The string of XML to convert into an object.
- **namespaces**: An optional array of namespaces.
- **mode**: Use `html` to convert an HTML DOM into an XML document.

```
{% set string_of_xml = 
"<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{{xml_encode(xml)}}
```

```
<?xml version="1.0"?>
<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>
```

## xml\_encode

Serialize an existing XML node back to a string.

The argument must be a `SimpleXMLElement`, usually from [xml\_decode](#xml_decode) or [xml\_xpath](#xml_xpath); anything else returns `false`.

There is also an [**xml\_encode** filter](/docs/scripting/filters/#xml_encode), and it is a different function that does the opposite job: it _builds_ XML from an array. Piping a node into the filter won't serialize it, and passing an array to this function returns `false`.

```
{% set string_of_xml = 
"<response xmlns=\"http://www.example.com/api/\">
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{{xml_encode(xml.client_id)}}
```

```
<client_id>1</client_id>
```

## xml\_tag

Return an XML node's element name.

`xml_tag(xml_node)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | A single XML node, usually from [xml\_xpath](#xml_xpath) |

**Returns:** The element's tag name. Returns `false` when `xml_node` isn't an XML node.

Despite the name, this inspects a node rather than building one. It belongs with [xml\_attr](#xml_attr) and [xml\_attrs](#xml_attrs) as the node-inspection family used after [xml\_decode](#xml_decode).

## xml\_xpath

Use the **xml\_xpath** function to extract values with XPath[2](#fn:xpath) queries:

```
{% set string_of_xml = 
"<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set client_id = xml_xpath(xml, '//client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
```

```
Client ID: 1
Invoice ID: 123
```

## xml\_xpath\_ns

You can define an XML namespace with the **xml\_xpath\_ns** function:

```
{% set string_of_xml = 
"<response xmlns=\"http://www.example.com/api/\">
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set xml = xml_xpath_ns(xml, 'ns', 'http://www.example.com/api/') %}
{% set client_id = xml_xpath(xml, '//ns:client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//ns:invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
```

```
Client ID: 1
Invoice ID: 123
```

## xml\_xpath\_remove

Remove elements from an XML document with an XPath query.

`xml_xpath_remove(xml,path)`

- **xml**: An XML object created by [xml\_decode](#xml_decode).
- **path**: The [XPath](#xml_xpath) query to match elements for removal.

```
{% set string_of_xml =
"<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set xml = xml_xpath_remove(xml, '//invoice_id') %}
{{xml_encode(xml)}}
```

```
<?xml version="1.0"?>
<response>
  <client_id>1</client_id>
</response>
```

[\< Commands](/docs/scripting/commands/)

[Filters \>](/docs/scripting/filters/)

# References

1. Wikipedia: XML - https://en.wikipedia.org/wiki/XML&nbsp;[↩](#fnref:xml)

2. Wikipedia: XPath - https://en.wikipedia.org/wiki/XPath&nbsp;[↩](#fnref:xpath)&nbsp;[↩2](#fnref:xpath:1)

