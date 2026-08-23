---
id: "docs-scripting-filters"
title: "Scripting Reference: Filters"
url: "https://cerb.ai/docs/scripting/filters/"
summary: "This webpage serves as a comprehensive scripting reference for filters available in Cerb's bot scripts and snippets. It details a wide array of filters, such as `abs`, `alphanum`, `append`, `array_sum`, `base_convert`, `base64_encode`, `capitalize`, `cerb_translate`, `date`, `escape`, `filter`, `hash`, `json_encode`, `markdown_to_html`, `md5`, `number_format`, `number_pretty`, `parse_csv`, `regexp`, `reverse`, `sha1`, `sort`, `split`, `striptags`, `title`, `trim`, `truncate`, `upper`, `url_encode`, and many more. Each filter is explained with its functionality, parameters, and examples, providing users with the necessary tools to manipulate data, format strings, handle arrays, and perform various transformations and calculations within Cerb's environment. This reference is essential for developers and users looking to enhance their automation and scripting capabilities in Cerb."
tags: ["docs", "docs-scripting"]
---
These filters are available in bot scripts and snippets:

- [abs](#abs)
- [alphanum](#alphanum)
- [append](#append)
- [array\_sum](#array_sum)
- [base\_convert](#base_convert)
- [base64\_decode](#base64_decode)
- [base64\_encode](#base64_encode)
- [base64url\_decode](#base64url_decode)
- [base64url\_encode](#base64url_encode)
- [batch](#batch)
- [bytes\_pretty](#bytes_pretty)
- [capitalize](#capitalize)
- [cerb\_translate](#cerb_translate)
- [column](#column)
- [context\_name](#context_name)
- [convert\_encoding](#convert_encoding)
- [csv](#csv)
- [date](#date)
- [date\_modify](#date_modify)
- [date\_pretty](#date_pretty)
- [default](#default)
- [escape](#escape)
- [filter](#filter)
- [first](#first)
- [format](#format)
- [hash](#hash)
- [hash\_hmac](#hash_hmac)
- [html\_to\_text](#html_to_text)
- [image\_info](#image_info)
- [indent](#indent)
- [join](#join)
- [json\_encode](#json_encode)
- [json\_pretty](#json_pretty)
- [kata\_encode](#kata_encode)
- [keys](#keys)
- [last](#last)
- [length](#length)
- [lower](#lower)
- [map](#map)
- [markdown\_to\_html](#markdown_to_html)
- [md5](#md5)
- [merge](#merge)
- [nl2br](#nl2br)
- [number\_format](#number_format)
- [number\_pretty](#number_pretty)
- [parse\_csv](#parse_csv)
- [parse\_emails](#parse_emails)
- [parse\_url](#parse_url)
- [parse\_user\_agent](#parse_user_agent)
- [permalink](#permalink)
- [qp\_decode](#qp_decode)
- [qp\_encode](#qp_encode)
- [quote](#quote)
- [reduce](#reduce)
- [regexp](#regexp)
- [repeat](#repeat)
- [replace](#replace)
- [reverse](#reverse)
- [round](#round)
- [secs\_pretty](#secs_pretty)
- [sha1](#sha1)
- [slice](#slice)
- [sort](#sort)
- [split](#split)
- [split\_crlf](#split_crlf)
- [split\_csv](#split_csv)
- [stat](#stat)
- [str\_pos](#str_pos)
- [str\_sub](#str_sub)
- [strip\_data\_uris](#strip_data_uris)
- [strip\_lines](#strip_lines)
- [strip\_pem\_blocks](#strip_pem_blocks)
- [strip\_url\_querystrings](#strip_url_querystrings)
- [striptags](#striptags)
- [title](#title)
- [tokenize](#tokenize)
- [trim](#trim)
- [truncate](#truncate)
- [unescape](#unescape)
- [upper](#upper)
- [url\_decode](#url_decode)
- [url\_encode](#url_encode)
- [values](#values)

## abs

Return the absolute value of a number:

```
{{-5|abs}}
```

```
5
```

## alphanum

Remove non-alphanumeric characters from a string:

```
{{"* Ignore spaces and non-alphanumeric characters+1$2%3!"|alphanum}}
```

```
Ignorespacesandnonalphanumericcharacters123
```

Also allow specific characters:

```
{{"* Ignore non-alphanumeric but allow spaces$%#!"|alphanum(' !')}}
```

```
Ignore nonalphanumeric but allow spaces!
```

## append

Append a suffix to the current text.

`|append(suffix, delimiter, trim)`

| **suffix** | The text to append. |
| **delimiter** | An optional delimiter to add between the current text and the suffix, only if the current text is non-empty. |
| **trim** | Optional characters to remove from the end of the current value (e.g. dangling commas). When omitted the trim is set to the same value as the delimiter. |

```
{% set emails = "customer@cerb.example" %}
{{emails|append('vendor@cerb.example', delimiter=', ')}}
```

```
customer@cerb.example, vendor@cerb.example
```

```
{% set emails = null %}
{{emails|append('vendor@cerb.example', delimiter=', ')}}
```

```
vendor@cerb.example
```

## array\_sum

Sum the numeric elements of an array.

```
{{array_sum([1,2,3,4,5])}}
```

```
15
```

## base\_convert

Convert between number system bases.

```
{% set int = 123456789 %}
{{int|base_convert(10,16)}}

{% set hex = '75bcd15' %}
{{hex|base_convert(16,10)}}
```

```
75bcd15

123456789
```

## base64\_decode

Decode a base64-encoded string:

```
{% set b64 = "VGhpcyB3YXMgYmFzZTY0LWVuY29kZWQ=" %}
{{b64|base64_decode}}
```

```
This was base64-encoded
```

## base64\_encode

Encode a string in base64:

```
{{"This was base64-encoded"|base64_encode}}
```

```
VGhpcyB3YXMgYmFzZTY0LWVuY29kZWQ=
```

## base64url\_decode

Decode a base64url-encoded string:

```
{% set b64 = "VGhpcyB3YXMgYmFzZTY0dXJsLWVuY29kZWQ" %}
{{b64|base64url_decode}}
```

```
This was base64url-encoded
```

## base64url\_encode

Encode a string in base64url:

```
{{"This was base64url-encoded"|base64url_encode}}
```

```
VGhpcyB3YXMgYmFzZTY0dXJsLWVuY29kZWQ
```

## batch

Break a list into smaller chunks with **batch**:

```
{% set items = ['red','blue','green'] %}
{{items|batch(2, '(empty)')|json_encode|json_pretty}}
```

```
[
    [
        "red",
        "blue"
    ],
    [
        "green",
        "(empty)"
    ]
]
```

## bytes\_pretty

Convert a number into a human readable number of bytes:

```
{{"123456789"|bytes_pretty(2)}}
```

```
123.46 MB
```

The optional argument determines the number of digits of precision.

## capitalize

Capitalize the first character of a string (and lowercase the rest):

```
{% set first_name = "kina" %}
{{first_name|capitalize}}
```

```
Kina
```

## cerb\_translate

Converts string IDs (like `status.open`) into text in the current worker's language.

```
The ticket is {{'status.open'|cerb_translate}}
```

```
The ticket is open.
```

## column

Extract a key from each item in an array as a new array. This has the same effect as the [array\_column()](/docs/scripting/functions/#array_column) function.

```
{% set people = [
  {'name':'Kina Halpue', 'email':'kina@cerb.example'},
  {'name':'Milo Dade', 'email': 'milo@cerb.example'}
] %}
{{people|column('email')|join(', ')}}
```

```
kina@cerb.example, milo@cerb.example
```

## context\_name

Convert a Cerb `context` ID into a human readable label.

`|context_name(type)`

| **type** | `singular`, `plural`, `id`, `uri` |

```
{{'cerberusweb.contexts.ticket'|context_name('singular')}}
{{'cerberusweb.contexts.task'|context_name('plural')}}
{{'worker'|context_name('id')}}
```

```
tickets
task
```

## convert\_encoding

Convert character encodings to the first argument from the second. If the second argument is blank then Cerb will attempt to auto-detect the current encoding.

```
{{"This has 😂 emoji"|convert_encoding('iso-8859-1', 'utf-8')}}
```

```
This has ? emoji
```

## csv

Format an array as a comma-separated values list. This is useful for exporting reports for Excel from bots.

Objects and dictionaries are automatically coerced to arrays.

```
{% set records = [
	{
		id: 1,
		subject: "Help with the API",
	},
	{
		id: 2,
		subject: "Automating email replies", 
	}
] %}
ID,Subject
{{records|csv}}
```

```
ID,Subject
1,"Help with the API"
2,"Automating email replies"
```

## date

Use the **date** filter to format a [string](/docs/scripting/strings/) or [variable](/docs/scripting/variables/) as a date:

```
{{'now'|date('F d, Y h:ia T')}}
{{'tomorrow 5pm'|date('D, d F Y H:i T')}}
{{'+2 weeks 08:00'|date('Y-m-d h:ia T')}}
```

```
December 12, 2017 11:50am PST
Wed, 13 December 2017 17:00 PST
2017-12-26 08:00am PST
```

You can use any of the formatting options from PHP DateTime::format.

The second parameter to the **date** filter can specify a timezone to use:

```
{% set ts_now = 'now' -%}

Bangalore: {{ts_now|date(time_format, 'Asia/Calcutta')}}
Berlin: {{ts_now|date(time_format, 'Europe/Berlin')}}
New York: {{ts_now|date(time_format, 'America/New_York')}}
```

```
Bangalore: December 13, 2017 01:27
Berlin: December 12, 2017 20:57
New York: December 12, 2017 14:57
```

You can get a Unix timestamp (seconds since 1-Jan-1970 00:00:00 UTC) from a date value with the `|date('U')` filter:

```
It has been {{'now'|date('U')}} seconds since {{'0'|date(null, 'UTC')}}
```

```
It has been 1513108417 seconds since January 1, 1970 00:00
```

## date\_modify

If you need to manipulate a date, create a date object with the [date](/docs/scripting/functions/#date) function and use the **date\_modify** filter:

```
{% set format = 'D, d M Y T' %}
{% set timestamp = date('now') %}
Now: {{timestamp|date(format)}}
+2 days: {{timestamp|date_modify('+2 days')|date(format)}}
```

```
Now: Tue, 12 Dec 2017 PST
+2 days: Thu, 14 Dec 2017 PST
```

## date\_pretty

Convert a Unix timestamp into a human-readable, relative date:

```
{% set timestamp = date("Jan 9 2002 10am", "America/Los_Angeles") %}
{{timestamp|date('U')|date_pretty}}
```

```
18 years ago
```

## default

You can use the **default** filter to give a default value to empty variables:

```
{% set name = '' %}
Hi {{name|default('there')}}
```

```
Hi there
```

## escape

Escape strings and variables with the following modes:

- `html`
- `js`
- `css`
- `url`
- `html_attr`

```
{{'This is "escaped" for Javascript'|escape('js')}}
{{'This is "escaped" for <b>HTML</b>'|e('html')}}
```

```
This\x20is\x20\x22escaped\x22\x20for\x20Javascript
This is &quot;escaped&quot; for <b>HTML</b>
```

## filter

Exclude items from an array using an arrow function.

`|filter(func)`

| **func(v,k)** | An arrow function that returns `true` (include) or `false` (exclude) for each item. It receives `v` (value) and `k` (key) as arguments. |

```
{% set arr = [1,2,3,4,5,6,7,8] %}
{{arr|filter((v,k) => v is even)|values|join(',')}}
```

```
2,4,6,8
```

## first

Return the first item of an array, object, or string:

```
{% set items = [1,2,3] %}
{{items|first}}
```

```
1
```

## format

Insert variables into a [string](/docs/scripting/strings/):

```
{% set who = "Kina" %}
{% set quantity = 120 %}
{{"%s closed %d tickets today!"|format(who, quantity)}}
```

```
Kina closed 120 tickets today!
```

For formatting specifiers, see: https://www.php.net/sprintf

## hash

Generate a one-way hash.

`|hash(algorithm, binary=false)`

| **algorithm** | The algorithm of the returned hash (e.g. `sha256`, `sha512`). |
| **binary** | Return raw binary data when `true` |

The **algorithm** can be one of: `crc32`, `md5`, `murmur3a`, `murmur3c`, `murmur3f`, `sha1`, `sha256`, `sha512/224`, `sha512/256`, `sha512`, `sha3-224`, `sha3-256`, `sha3-384`, `sha3-512`, `whirlpool`, `xxh32`, `xxh64`, `xxh3`, `xxh128`

```
{% set text = 'This string will be hashed' %}
SHA-512: {{text|hash('sha512')}}
Murmur3a: {{text|hash('murmur3a')}}
xxh128: {{text|hash('xxh128')}}
```

```
SHA-512: 8b0a3e297c0447e43e20e966d1cbf4a20163c9ddebb95e1d4ba44e2542c1915597375c1a39dfce4f5786d1d187a4ce5f780817d34632fcbc571694533b3961f0
Murmur3a: 4a9df623
xxh128: 0da37dd25c7ee8945e2947cd89e86549
```

## hash\_hmac

Generate a hash-based message authentication code (HMAC[1](#fn:hmac)) using a secret key.

`|hash_hmac(secret_key, algorithm, binary)`

| **secret\_key** | The secret key used to generate the HMAC digest |
| **algorithm** | The algorithm of the returned hash (e.g. `sha256`, `sha512`). See: hash\_hmac\_algos |
| **binary** | Return raw binary data when `true`, otherwise lowercase hex (default) |

For instance, you can use this to sign parameters in a survey URL to verify that the recipient didn't modify them.

```
{% set data = {'email': 'kina@cerb.example', 'survey_id': 123} %}
{{data|json_encode|hash_hmac("THIS IS SECRET","sha256")}}
```

```
5514f8aed3b39159d455f9a8f74b5d23d4f96391fa4a27d1bea6f940cb7d410f
```

Provide your own value for THIS IS SECRET. You an store it in the bot configuration.

## html\_to\_text

Convert HTML content to plain text.

`|html_to_text(truncate=50000)`

| **truncate** | The maximum length to parse (bytes) |

```
{% set html %}
<p>
	This has <b>bold</b> and <u>underlined</u> text with <a href="https://cerb.ai/">links</a>.
</p>
<p>
	List:
	<ul>
		<li>This</li>
		<li>is</li>
		<li>a</li>
		<li>list</li>
	</ul>
</p>
{% endset %}
{{html|html_to_text}}
```

```
This has bold and underlined text with links <https://cerb.ai/>.
 
List:
* This
* is
* a
* list
```

## image\_info

Returns information about an image. The image may be provided as bytes or in data URI format.

`|image_info()`

```
{% set image_string %}
data:image/png;base64,iVBORw0KGgoAAAA....
{% endset %}
{{image_string|image_info|json_encode|json_pretty}}
```

```
{
    "width": 100,
    "height": 100,
    "channels": 3,
    "bits": 8,
    "type": "image/png"
}
```

## indent

Prefix the start of each line with a given marker in a block of text.

`|indent(marker, start_line)`

| **marker** | The prefix to add to the beginning of each line. |
| **start\_line** | The line number to start prefixing from (0-based). |

```
{% set text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris eget diam 
eu orci hendrerit elementum. Suspendisse egestas, dolor at efficitur sollicitudin, magna eros 
scelerisque risus, at tincidunt massa augue a eros. Nullam scelerisque luctus suscipit. Sed 
dui metus, rhoncus sed diam non, pretium maximus augue. Phasellus feugiat justo mi, in 
tristique quam euismod pellentesque. Curabitur ut libero sagittis sem semper ultrices. Nullam 
et mi id arcu vulputate fringilla ut quis nibh. Fusce lobortis magna eu quam porta scelerisque.
Suspendisse maximus fringilla tellus, a pellentesque sem tincidunt sit amet." -%}

{{text|indent('> ')}}
```

```
> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris eget diam 
> eu orci hendrerit elementum. Suspendisse egestas, dolor at efficitur sollicitudin, magna eros 
> scelerisque risus, at tincidunt massa augue a eros. Nullam scelerisque luctus suscipit. Sed 
> dui metus, rhoncus sed diam non, pretium maximus augue. Phasellus feugiat justo mi, in 
> tristique quam euismod pellentesque. Curabitur ut libero sagittis sem semper ultrices. Nullam 
> et mi id arcu vulputate fringilla ut quis nibh. Fusce lobortis magna eu quam porta scelerisque.
> Suspendisse maximus fringilla tellus, a pellentesque sem tincidunt sit amet.
```

## join

Convert an [array](/docs/scripting/arrays-objects/) to a string with delimiters:

```
{% set items = [1,2,3] %}
{{items|join(',')}}
{{items|join(' ')}}
```

```
1,2,3
1 2 3
```

## json\_encode

You can encode any variable as a JSON string with the **json\_encode** filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode}}
```

```
{"name":"Joe Customer","order_id":54321,"status":{"text":"shipped","tracking_id":"Z1F238"}}
```

## json\_pretty

You can _"prettify"_ a JSON string with the **json\_pretty** filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode|json_pretty}}
```

```
{
  "name": "Joe Customer",
  "order_id": 54321,
  "status": {
    "text": "shipped",
    "tracking_id": "Z1F238"
  }
}
```

## kata\_encode

Emit an object/array as a KATA text block:

```
{% set object = {
	colors: ["red","green","blue"],
	size: 100,
} %}
{{object|kata_encode}}
```

```
colors@list:
  red
  green
  blue
size: 100
```

## keys

Return the keys of an array or object:

```
{% set list = ['red','green','blue'] %}
{% set obj = { 'name': 'Kina', 'age': 35, 'title': 'Customer Support Supervisor'} %}

{{list|keys|join(',')}}
{{obj|keys|json_encode}}
```

```
0,1,2

["name","age","title"]
```

## last

Return the last item of an array, object, or string:

```
{% set items = [1,2,3] %}
{{items|last}}
```

```
3
```

## length

Return the length of a string or array:

```
{{"This is a string"|length}}
{{[1,2,3,4,5]|length}}
```

```
16
5
```

## lower

Convert a string to lowercase:

```
{{"WHY ARE YOU YELLING?"|lower}}
```

```
why are you yelling?
```

## map

Apply a function to each item in an array to create a new array.

`|map(func)`

| **func(v,k)** | An arrow function that returns the new value for each item. It receives `v` (value) and `k` (key) as arguments. |

```
{% set samples = [
	[1,2,3,4,5],
	[6,7,8,9,10],
	[1,3,5,7,9],
	[2,4,6,8,10],
] %}
Averages:
{{samples|map((v,k) => array_sum(v)/(samples[k]|length))|join(', ')}}
```

```
Averages:
3, 8, 5, 6
```

## markdown\_to\_html

Convert Markdown[2](#fn:markdown) formatting to HTML:

```
{% set markdown %}
This is **bold** text with a link
{% endset %}
{{markdown|markdown_to_html}}
```

```
<p>This is <strong>bold</strong> text with a <a href="https://cerb.ai/">link</a></p>
```

## md5

Generate an MD5[3](#fn:md5) hash for a string:

```
{{"You can verify this hash"|md5}}
```

```
1c20552e3bae1c4711cf697137002581
```

## merge

Combine two arrays or objects:

```
{% set mfgs = ['Tesla','Ford'] %}
{% set mfgs = mfgs|merge(['Toyota','GM']) %}
{{mfgs|json_encode}}
```

```
["Tesla","Ford","Toyota","GM"]
```

## nl2br

Convert newline characters (`\n`) to HTML breaks (`<br />`):

```
{% set text = "This has
line feeds
in the text
"%}
{{text|nl2br}}
```

```
This has<br />
line feeds<br />
in the text<br />
```

## number\_format

Format a number with thousand separators and decimal places:

```
{% set cost = 16858 %}
That will be ${{cost|number_format(2,'.',',')}}
```

```
That will be $16,858.00
```

## number\_pretty

Format a large number in a human-readable form, with a magnitude suffix of `K`, `M`, `B`, or `T`:

```
{{12345678|number_pretty(1)}}
```

```
12.3M
```

The optional argument determines the number of digits of precision, and defaults to none:

```
{{32768|number_pretty}}
```

```
32K
```

This **truncates** rather than rounds, which is where it parts ways with [`bytes_pretty`](#bytes_pretty). A 32,768-token context window is universally called "32K", never "33K", and truncating also keeps `999999` from rounding up into a nonsensical `1000K`.

A number below 1,000 is returned as-is, and a negative number keeps its sign. A non-numeric value returns an empty string.

## parse\_csv

Parse a document with rows of comma-separated columns. Returns an array of rows with elements for columns.

`parse_csv(separator=',',enclosure='"',escape='\\')`

| **separator** | An optional character to separate fields by. Defaults to comma (`,`). |
| **enclosure** | An optional character to enclose fields. Defaults to double quote (`"`). The enclosure field can be used inside a field by doubling it (as an alternative to escaping). |
| **escape** | An optional character to escape special characters (e.g. `\n`). This defaults to backslash (`\`), and escaping can be disabled with an empty string. |

```
{% set text %}
"Person Name",Email,Organization
"Kina Halpue",kina@cerb.example,Cerb
"Claire Bertin",c.bertin@baston.example,"Baston Defence"
{% endset %}
{{text|parse_csv|json_encode|json_pretty}}
```

```
[
    [
        "Person Name",
        "Email",
        "Organization"
    ],
    [
        "Kina Halpue",
        "kina@cerb.example",
        "Cerb"
    ],
    [
        "Claire Bertin",
        "c.bertin@baston.example",
        "Baston Defence"
    ]
]
```

## parse\_emails

Parse a delimited string of email addresses into an object. This also assists with email validation.

```
{% set emails = "kina@cerb.example, milo@cerb.example, karl" %}
{{emails|parse_emails|json_encode|json_pretty}}
```

```
{
    "kina@cerb.example": {
        "full_email": "kina@cerb.example",
        "email": "kina@cerb.example",
        "mailbox": "kina",
        "host": "cerb.example",
        "personal": null
    },
    "milo@cerb.example": {
        "full_email": "milo@cerb.example",
        "email": "milo@cerb.example",
        "mailbox": "milo",
        "host": "cerb.example",
        "personal": null
    },
    "karl@localhost": {
        "full_email": "karl@localhost",
        "email": "karl@localhost",
        "mailbox": "karl",
        "host": "localhost",
        "personal": null
    }
}
```

## parse\_url

Parse a URL string into an object for validation.

```
{% set url = "https://cerb.ai/search?q=oauth2#fragment" %}
{{url|parse_url|json_encode|json_pretty}}
```

```
{
    "scheme": "https",
    "host": "cerb.ai",
    "path": "/search",
    "query": "q=oauth2",
    "fragment": "fragment"
}
```

## parse\_user\_agent

Parse a user-agent string into an object for validation.

```
{% set user_agent %}
Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15
{% endset %}
{{user_agent|parse_user_agent|json_encode}}
```

```
{
    "platform": "Macintosh",
    "browser": "Safari",
    "version": "16.1"
}
```

## permalink

```
{% set text = "This is the title of a record!" %}
{{text|permalink|lower}}
```

```
this-is-the-title-of-a-record
```

## qp\_decode

Decode a string in quoted-printable format.

```
{% set message %}
Hello and welcome to our new service! =F0=9F=98=80

We're delighted =F0=9F=8E=89 to have you as a member of our community.
This is a sample email with emojis =F0=9F=9A=80 and quoted-printable encodi=
ng.

Have a great day! =F0=9F=8C=88

Best regards,
The Team =F0=9F=91=8B
{% endset %}

{{message|qp_decode}}
```

```
Hello and welcome to our new service! 😀

We're delighted 🎉 to have you as a member of our community.
This is a sample email with emojis 🚀 and quoted-printable encoding.

Have a great day! 🌈

Best regards,
The Team 👋
```

## qp\_encode

Encode a string in quoted-printable format. For instance, creating tickets with emoji using [email.parse:](/docs/automations/commands/email.parse/).

```
{% set message %}
Hello and welcome to our new service! 😀

We're delighted 🎉 to have you as a member of our community.
This is a sample email with emojis 🚀 and quoted-printable encoding.

Have a great day! 🌈

Best regards,
The Team 👋
{% endset %}

{{message|qp_encode}}
```

```
Hello and welcome to our new service! =F0=9F=98=80

We're delighted =F0=9F=8E=89 to have you as a member of our community.
This is a sample email with emojis =F0=9F=9A=80 and quoted-printable encodi=
ng.

Have a great day! =F0=9F=8C=88

Best regards,
The Team =F0=9F=91=8B
```

## quote

```
{% set text = "This is a message you are replying to.

You should quote it.
" %}
{{text|quote}}
```

```
> This is a message you are replying to.
>
> You should quote it.
```

## reduce

Reduce an array of items into a single output value.

`|reduce(func,initial)`

| **func(carry,v)** | An arrow function that returns the new carry value after each item. It receives the old `carry` value and the current item `v` (value). |
| **initial** | An optional starting value for `carry`. |

```
{% set samples = [
	[1,2,3,4,5],
	[6,7,8,9,10],
	[1,3,5,7,9],
	[2,4,6,8,10],
] %}
Sum:
{{samples|reduce((carry,v) => carry + array_sum(v))}}
```

```
Sum:
110
```

## regexp

You can use regular expressions[4](#fn:regexp) with the **regexp** filter to match or extract patterns.

`|regexp(pattern,group)`

- `pattern` The regular expression pattern to match.
- `group`: The matching group `()` from the pattern to extract as a string.

Example:

```
{% set text = "Your Amazon Order #Z-1234-5678-9 has shipped!" %}
{% set order_id = text|regexp("/Amazon Order #([A-Z0-9\-]+)/", 1) %}
Amazon Order #: {{order_id}}
```

```
Amazon Order #: Z-1234-5678-9
```

If you need to escape characters in your regexp pattern, you should use a [set](/docs/scripting/commands/#set) block rather than a string:

```
{% set pattern %}
#\[.*?\] (.*)#
{% endset %}
{% set bracketed_text = "[ABC-123-45678] Order Processing - 7 Days" %}
{{bracketed_text|regexp(pattern, 1)}}
```

```
Order Processing - 7 Days
```

## repeat

Repeat a string a given number of times.

`|repeat(times)`

| **times** | The number of times to repeat the string. |

```
{{"*"|repeat(5)}}
```

```
*****
```

## replace

```
{{"I really like %food%"|replace({'%food%':'ice cream'})}}
```

```
I really like ice cream
```

## reverse

Reverse a string or array:

```
{{"Leonardo da Vinci"|reverse}}
{{[1,2,3,4,5]|reverse|join}}
```

```
icniV ad odranoeL
54321
```

The optional preserve\_keys parameter will maintain object keys.

## round

Round a number with desired precision.

`|round(precision,method)`

- `precision` The number of floating point digits.
- `method`: 
  - common
  - ceil
  - floor

```
{% set pi = 3.141592653589793238462643383279502884197169399375105820974944592307816406286 %}
{{pi|round}}
{{pi|round(5)}}
{{pi|round(5,'ceil')}}
```

```
3
3.14159
3.1416
```

## secs\_pretty

```
{{"300"|secs_pretty}}
{{"86400"|secs_pretty}}
{{"604800"|secs_pretty()}}
```

```
5 mins
1 day
1 week
```

## sha1

Generate an SHA-1[5](#fn:sha1) hash for a string:

```
{{"You can verify this hash"|sha1}}
```

```
50ae61a375994fd178cd47fc7d29f7ec5724dda3
```

## slice

Extract part of a string, array, or object.

`|slice(start, length, preserve_keys)`

```
{{[1,2,3,4,5]|slice(2,2)|json_encode}}
{{"This is some text"|slice(0,4)}}
```

```
[3,4]
This
```

## sort

Sort an array:

```
{% set x = [9,5,1,6,4,3] %}
{{x|sort|slice(0,6)|json_encode}}
```

```
[1,3,4,5,6,9]
```

You can also provide an arrow function as a custom comparator for advanced sorting rules. The spaceship operator (`<=>`) automatically returns in comparator format (e.g. `-1`, `0`, or `1`):

- (A \<=\> B) \< 0 is true if A \< B
- (A \<=\> B) \> 0 is true if A \> B
- (A \<=\> B) == 0 is true if A and B are equal/equivalent

```
{% set items = [
    {name: "Item C", priority: 3},
    {name: "Item A", priority: 1},
    {name: "Item B", priority: 2}
] %}
{{items|sort((a,b) => a.priority <=> b.priority)|column('name')|join(', ')}}
```

```
Item A, Item B, Item C
```

## split

Convert a string to an array with the given delimiter.

`|split(delimiter, limit)`

```
{{"1,2,3,4,5"|split(',')|json_encode}}
```

```
["1","2","3","4","5"]
```

## split\_crlf

Split a string on any combination of carriage return (`\r`) and linefeed (`\n`) delimiters.

`|split_crlf(keep_blanks=false,trim_lines=true)`

| **keep\_blanks** | Remove lines that are comprised of only whitespace. |
| **trim\_lines** | Remove whitespace before and after each line. |

```
{% set rainbow = "red
orange
yellow
green
blue
indigo
violet" %}
{{rainbow|split_crlf|json_encode}}
```

```
["red","orange","yellow","green","blue","indigo","violet"]
```

## split\_csv

Split a string on comma delimiters. This automatically handles whitespace padding.

```
{% set coins = "BTC, ETH ,LTC" %}
{{coins|split_csv|json_encode}}
```

```
["BTC","ETH","LTC"]
```

## stat

Calculate a statistical measure for a given array of numbers.

`|stat(measure, decimals)`

| **measure** | `count`, `max`, `mean`, `median`, `min`, `mode`, `stdevp`, `stdevs`, `sum`, `varp`, `vars` |
| **decimals** | The number of decimal places for rounding |

```
{% set samples = [1,2,3,4,5,6,7,8,9,10] %}
{{samples|stat(measure='median')}}
```

```
5.5
```

## str\_pos

Return the position of a substring (needle) within a larger text (haystack). This returns `-1` if the substring is not found.

`|str_pos(needle, offset, ignoreCase)`

| **needle** | The substring to search for. |
| **offset** | The position to start searching from. |
| **ignoreCase** | `true` for case-insensitive matching, `false` for case-sensitive |

```
{% set alphabet %}
ABCDEFGHIJKLMNOPQRSTUVWXYZ
{% endset %}
{{alphabet|str_pos(needle='hi', offset=0, ignoreCase=true)}}
```

```
7
```

## str\_sub

Extract a substring from a larger string using starting and ending positions. This is an alternative to [|slice(from,length)](/docs/scripting/filters/#slice).

`|str_sub(from, to)`

| **from** | The position to start extracting a substring from (inclusive). |
| **to** | The position to end extraction at (exclusive). |

```
{% set alphabet %}
ABCDEFGHIJKLMNOPQRSTUVWXYZ
{% endset %}
{{alphabet|str_sub(7,9)}}
```

```
HI
```

## strip\_data\_uris

Remove the base64-encoded content from data URIs in a text block. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where the encoded payload contributes noise rather than searchable terms.

`|strip_data_uris`

```
{% set html %}
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...">
{% endset %}
{{html|strip_data_uris}}
```

```
<img src="data:image/png;base64,">
```

## strip\_lines

Remove lines in a text block that begin with one of the given `prefixes`.

`|strip_lines(prefixes)`

```
{% set email_message %}
> This is some quoted text
> on multiple lines

This is the original message
{% endset %}
{{email_message|strip_lines(prefixes='>')}}
```

```
This is the original message
```

## strip\_pem\_blocks

Remove PEM-formatted blocks like PGP signatures, public keys, and SSL certificates from a block of text. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where the long base64 payloads contribute noise rather than searchable terms.

`|strip_pem_blocks`

```
{% set message %}
Hello,

Here is my reply.

-----BEGIN PGP SIGNATURE-----

iQIzBAEBCAAdFiEE...
-----END PGP SIGNATURE-----
{% endset %}
{{message|strip_pem_blocks}}
```

```
Hello,

Here is my reply.
```

## strip\_url\_querystrings

Remove the query string portion from URLs in a text block. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where tracking parameters and session IDs add noise.

`|strip_url_querystrings`

```
{% set text %}
Check out https://example.com/page?utm_source=email&utm_campaign=q4 for details.
{% endset %}
{{text|strip_url_querystrings}}
```

```
Check out https://example.com/page for details.
```

## striptags

Remove HTML tags from a string.

```
{% set html = "This <b>string</b> has <b>HTML</b> tags!" %}
{{html|striptags}}
```

```
This string has HTML tags!
```

## title

```
{% set book_title = "the ultimate bot builder handbook" %}
{{book_title|title}}
```

```
The Ultimate Bot Builder Handbook
```

## tokenize

Return an array of word tokens from a text block. This ignores punctuation and returns tokens in the order they appear, including duplicates.

`|tokenize`

```
{% set message %}
KATA ("Key Annotated Tree of Attributes") is a human-friendly format for modeling structured 
data that is used throughout Cerb to describe configurations, customizations, sheets, and 
automations. KATA was inspired by YAML but avoids many of its pitfalls.
{% endset %}
{{array_count_values(message|tokenize)|sort|reverse|json_encode|json_pretty}}
```

```
{
    "kata": 2,
    "of": 2,
    "is": 2,
    "that": 1,
    "data": 1,
    "structured": 1,
    "modeling": 1,
    "for": 1,
    "format": 1,
    "friendly": 1,
    "human": 1,
    "a": 1,
    "attributes": 1,
    "tree": 1,
    "annotated": 1,
    "used": 1,
    "key": 1,
    "throughout": 1,
    "to": 1,
    "its": 1,
    "many": 1,
    "avoids": 1,
    "but": 1,
    "yaml": 1,
    "by": 1,
    "cerb": 1,
    "inspired": 1,
    "automations": 1,
    "and": 1,
    "sheets": 1,
    "customizations": 1,
    "configurations": 1,
    "describe": 1,
    "was": 1,
    "pitfalls": 1
}
```

## trim

Remove leading and/or trailing whitespace from a string.

`|trim(character_mask, side)`

- `character_mask` The characters to remove
- `side`
  - both
  - left
  - right

```
{% set str = " whitespace " %}
{{str|trim}}
{{str|trim(' ', 'left')}}
{{str|trim(' ', side='right')}}
```

```
whitespace
whitespace    
    whitespace
```

## truncate

Ensure that a string is no longer than the given limit.

`|truncate(limit)`

```
{% set str = "This string is longer than we'd prefer" %}
{{str|truncate(11)}}
```

```
This string...
```

## unescape

Decode HTML entities:

```
{{"&amp;quot;iPhone&amp;quot; is &amp;copy; Apple, Inc."|unescape}}
```

```
"iPhone" is © Apple, Inc.
```

## upper

Convert a string to uppercase:

```
{{"I can't hear you!"|upper}}
```

```
I CAN'T HEAR YOU!
```

## url\_decode

Decode a URL query string into an array:

```
{% set query = "name=Kina&action=light_on" %}
{{query|url_decode('json')}}
```

```
{"name":"Kina","action":"light_on"}
```

## url\_encode

Build a URL query string from an array:

```
{% set args = {"name": "Kina", "action": "light_on" } %}
{{args|url_encode}}
```

```
name=Kina&action=light_on
```

## values

Return the values of an array with sequential keys. This is the filter equivalent of the [array\_values()](/docs/scripting/functions/#array_values) function.

```
{% set countries = {
  'CA': 'Canada',
  'CN': 'China',
  'DE': 'Germany',
  'IN': 'India',
  'MX': 'Mexico',
  'US': 'United States',
} %}
{{countries|values|json_encode}}
```

```
["Canada","China","Germany","India","Mexico","United States"]
```

[\< Functions](/docs/scripting/functions/)

[Tests \>](/docs/scripting/tests/)

# References

1. Wikipedia: Hash-based message authentication code (HMAC) - https://en.wikipedia.org/wiki/Hash-based\_message\_authentication\_code&nbsp;[↩](#fnref:hmac)

2. Wikipedia: Markdown - https://en.wikipedia.org/wiki/Markdown&nbsp;[↩](#fnref:markdown)

3. Wikipedia: MD5 - https://en.wikipedia.org/wiki/MD5&nbsp;[↩](#fnref:md5)

4. Wikipedia: Regular Expression - https://en.wikipedia.org/wiki/Regular\_expression&nbsp;[↩](#fnref:regexp)

5. Wikipedia: SHA-1 - https://en.wikipedia.org/wiki/SHA-1&nbsp;[↩](#fnref:sha1)

