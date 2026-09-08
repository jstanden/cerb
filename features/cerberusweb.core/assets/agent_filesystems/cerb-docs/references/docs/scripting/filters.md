---
id: "docs-scripting-filters"
title: "Scripting Reference: Filters"
url: "https://cerb.ai/docs/scripting/filters/"
summary: "This webpage serves as a comprehensive scripting reference for filters available in Cerb's automation scripting and snippets. It details a wide array of filters, such as `abs`, `alphanum`, `append`, `array_sum`, `base_convert`, `base64_encode`, `bin2hex`, `capitalize`, `cerb_translate`, `date`, `escape`, `filter`, `hash`, `hex2bin`, `json_encode`, `markdown_to_html`, `md5`, `number_format`, `number_pretty`, `parse_csv`, `regexp`, `reverse`, `sha1`, `sort`, `split`, `striptags`, `title`, `trim`, `truncate`, `upper`, `url_encode`, and many more. Each filter is explained with its functionality, parameters, and examples, providing users with the necessary tools to manipulate data, format strings, handle arrays, and perform various transformations and calculations within Cerb's environment. This reference is essential for developers and users looking to enhance their automation and scripting capabilities in Cerb."
tags: ["docs", "docs-scripting"]
---
These filters are available in automation scripting and snippets:

- [abs](#abs)
- [alphanum](#alphanum)
- [append](#append)
- [base\_convert](#base_convert)
- [base64\_decode](#base64_decode)
- [base64\_encode](#base64_encode)
- [base64url\_decode](#base64url_decode)
- [base64url\_encode](#base64url_encode)
- [batch](#batch)
- [bin2hex](#bin2hex)
- [bytes\_pretty](#bytes_pretty)
- [capitalize](#capitalize)
- [cerb\_translate](#cerb_translate)
- [column](#column)
- [context\_alias](#context_alias)
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
- [hex2bin](#hex2bin)
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
- [raw](#raw)
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
- [spaceless](#spaceless)
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
- [xml\_encode](#xml_encode)
  - [Hints](#hints)

https://www.youtube.com/embed/7rp_9WA2W1s

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
    {
        "2": "green",
        "3": "(empty)"
    }
]
```

The padded final chunk keeps the numeric keys from the original list, so [json\_encode](#json_encode) emits it as an object rather than an array. Pipe it through [values](#values) first if you need consistent arrays.

## bin2hex

Convert a binary string to its hexadecimal representation:

```
{{"Cerb"|bin2hex}}
```

```
43657262
```

Every byte becomes exactly two lowercase hex digits, so the result is twice as long as the input and safe to slice at any even offset. That's what makes it useful on decoded binary – the bytes of a message header, a hash digest, or a packed identifier – where the raw string would be unprintable and slicing it by character could split a multi-byte sequence.

```
{% set bytes = "AAECf/8="|base64_decode %}
{{bytes|bin2hex}}
```

```
0001027fff
```

Anything that isn't a string returns nothing at all. Use [hex2bin](#hex2bin) to convert back.

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

## context\_alias

Convert a Cerb `context` ID into its URI alias.

`|context_alias`

```
{{'cerberusweb.contexts.ticket'|context_alias}}
```

```
ticket
```

This is [context\_name](#context_name) with its `type` pinned to `uri`. `{{x|context_alias}}` and `{{x|context_name('uri')}}` are the same call.

## context\_name

Convert a Cerb `context` ID into a human readable label.

`|context_name(type)`

| **type** | `singular`, `plural` (default), `singular_short`, `plural_short`, `id`, `uri` |

```
{{'cerberusweb.contexts.ticket'|context_name('singular')}}
{{'cerberusweb.contexts.task'|context_name('plural')}}
{{'worker'|context_name('id')}}
```

```
ticket
tasks
cerberusweb.contexts.worker
```

Every form accepts either an alias or a full context string, so `worker` and `cerberusweb.contexts.worker` are interchangeable as input.

Some record types register short names as well. For tickets, `singular_short` returns `convo` and `plural_short` returns `mail`.

Those two don't look like a pair, and that's expected: a record type can register many names, each declaring which forms it can fill, and every form is claimed by the first name that qualifies for it. `mail` is declared as singular, plural, _and_ short, so it takes `plural_short` before a later name can.

## convert\_encoding

Convert character encodings to the first argument from the second. If the second argument is blank then Cerb will attempt to auto-detect the current encoding.

`|convert_encoding(to, from)`

Converting _away_ from UTF-8 produces bytes that a UTF-8 page can't display, so a single conversion in that direction looks like mojibake even when it succeeded. A round trip shows the text survives intact:

```
{{"Café"|convert_encoding('iso-8859-1', 'utf-8')|convert_encoding('utf-8', 'iso-8859-1')}}
```

```
Café
```

When any character can't be represented in the target encoding, the _entire_ conversion fails and returns an empty string. It is not a partial result with the unmappable characters removed. One emoji loses the whole message, and no error is reported.

Both of these return nothing: {{"This has 😂 emoji"|convert\_encoding('iso-8859-1', 'utf-8')}} and {{"Café"|convert\_encoding('ASCII', 'utf-8')}}

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

Relative English date strings like these are resolved when the script runs, so their output isn't shown here. The examples below use a fixed date instead.

You can use any of the formatting options from PHP DateTime::format.

The second parameter to the **date** filter is the timezone the result is displayed in. It does not change the timezone a date string is _parsed_ in, so include the zone in the string itself when it matters:

```
{% set time_format = 'F j, Y H:i' %}
{% set ts = date('2017-12-12 14:57 America/New_York') -%}

Bangalore: {{ts|date(time_format, 'Asia/Kolkata')}}
Berlin: {{ts|date(time_format, 'Europe/Berlin')}}
New York: {{ts|date(time_format, 'America/New_York')}}
```

```
Bangalore: December 13, 2017 01:27
Berlin: December 12, 2017 20:57
New York: December 12, 2017 14:57
```

You can get a Unix timestamp (seconds since 1-Jan-1970 00:00:00 UTC) from a date value with the `|date('U')` filter:

```
{{"2017-12-12 14:57 America/New_York"|date('U')}}
{{"1513108620"|date('F j, Y H:i', 'America/New_York')}}
```

```
1513108620
December 12, 2017 14:57
```

## date\_modify

If you need to manipulate a date, create a date object with the [date](/docs/scripting/functions/#date) function and use the **date\_modify** filter:

```
{% set format = 'D, d M Y' %}
{% set timestamp = date('2017-12-12', 'UTC') %}
Then: {{timestamp|date(format, 'UTC')}}
+2 days: {{timestamp|date_modify('+2 days')|date(format, 'UTC')}}
```

```
Then: Tue, 12 Dec 2017
+2 days: Thu, 14 Dec 2017
```

## date\_pretty

Convert a Unix timestamp into a human-readable, relative date:

```
{% set an_hour_ago = date('now')|date('U') - 3600 %}
{% set three_days_ago = date('now')|date('U') - (86400 * 3) %}
{% set in_two_hours = date('now')|date('U') + 7200 %}
{{an_hour_ago|date_pretty}}
{{three_days_ago|date_pretty}}
{{in_two_hours|date_pretty}}
```

```
1 hour ago
3 days ago
2 hours
```

A date in the future has no suffix, as in the `2 hours` above.

This filter takes a Unix timestamp, not a date object. Piping a [date()](/docs/scripting/functions/#date) object straight in returns an empty string and reports no error, so convert it first with |date('U').

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
This\u0020is\u0020\u0022escaped\u0022\u0020for\u0020Javascript
This is &quot;escaped&quot; for &lt;b&gt;HTML&lt;/b&gt;
```

`e` is a shorthand alias for `escape`, as in the second line above. Both are available.

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

## hex2bin

Convert a hexadecimal string back to the binary string it represents. This is the inverse of [bin2hex](#bin2hex):

```
{{"43657262"|hex2bin}}
```

```
Cerb
```

The input must be an even number of hexadecimal digits and nothing else. An odd-length value, any non-hex character, and an empty string all return nothing rather than raising an error, so validate the source before relying on the result.

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

The input must already be a JSON string. Given anything else – an array or a dictionary – the filter renders nothing at all, with no error, so pipe it through [json\_encode](#json_encode) first, as the example does.

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
size@int: 100
```

Each value is labeled with the [annotation](/docs/kata/#key-annotations) for its type – `@int` for a whole number, `@float` for a decimal, `@bool` for true or false – so a reader that applies those annotations gets numbers and booleans back rather than text. A value with no annotation is text.

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

Cells are addressed by position rather than by name. A header row comes back as the first row of data, not as keys – so reach a column with an index like `{{row.0}}`, and skip the header yourself when the document has one.

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
        "personal": ""
    },
    "milo@cerb.example": {
        "full_email": "milo@cerb.example",
        "email": "milo@cerb.example",
        "mailbox": "milo",
        "host": "cerb.example",
        "personal": ""
    }
}
```

Addresses that don't validate are left out of the result. In the example above, the bare `karl` has no domain, so it doesn't appear at all – compare the number of keys against the number of addresses you passed in to detect that.

## parse\_url

Parse a URL string into an object for validation.

The filter takes no arguments and always returns the whole record. Reach a single component from the result – `{{url|parse_url.host}}` – rather than asking for one by name.

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
{"platform":"Macintosh","browser":"Safari","version":"16.1"}
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

## raw

Mark a value as markup that is already safe, so it isn't escaped.

Cerb doesn't escape template output in most places – automations, snippets, email signatures, and mail templates all emit values as they are – so on those surfaces `raw` makes no difference to what you see:

```
{% set s = "<b> <i>x</i> </b>" %}
{{s}}
{{s|raw}}
```

```
<b> <i>x</i> </b>
<b> <i>x</i> </b>
```

Where it does matter is with filters that escape their own input before they run, and they don't all fail the same way.

[spaceless](/docs/scripting/commands/#spaceless) matches on the `>` and `<` around whitespace, which are the characters escaping replaces. It finds nothing to collapse and returns the text unchanged, with no error.

[nl2br](#nl2br) matches on line breaks, which escaping leaves alone, so it still inserts its `<br />` – but the markup around it comes back escaped.

Piping through `raw` first prevents both:

```
{% set s = "<b> <i>x</i> </b>" %}
{{s|spaceless}}
{{s|raw|spaceless}}
```

```
&lt;b&gt; &lt;i&gt;x&lt;/i&gt; &lt;/b&gt;
<b><i>x</i></b>
```

[Sheet](/docs/sheets/) cells and HTML and JavaScript widget templates _do_ escape their output. On those surfaces raw suppresses that escaping, but generally only when it comes last in the chain, because a later filter can produce a new value that is escaped again.

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

## spaceless

Remove the whitespace between HTML tags. `spaceless` is a **filter**, not a command, and it's applied to a block with [apply](/docs/scripting/commands/#apply): `{% apply spaceless %}`.

It's Twig's own filter, and it's **deprecated as of Twig 3.12** and scheduled for removal in Twig 4.0. See [spaceless](/docs/scripting/commands/#spaceless) on the Commands page for the example, the history, and the non-deprecated alternative.

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

Remove the contents of PEM-formatted blocks like PGP signatures, public keys, and SSL certificates from a block of text. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where the long base64 payloads contribute noise rather than searchable terms.

The `-----BEGIN-----` and `-----END-----` markers are kept, on one line, so the text still records that a block was there.

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

-----BEGIN PGP SIGNATURE----- -----END PGP SIGNATURE-----
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
support support support support ticket ticket ticket reply reply queue
{% endset %}
{{array_count_values(message|tokenize)|sort|reverse|json_encode|json_pretty}}
```

```
{
    "support": 4,
    "ticket": 3,
    "reply": 2,
    "queue": 1
}
```

Sorting counts puts the most frequent tokens first, but **sort** is not stable for equal values, so tokens with the same count can come back in any order.

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

Ensure that a string is no longer than the given limit. The limit includes the separator, so the result is never longer than `limit` characters.

`|truncate(limit, separator)`

| **limit** | The maximum length of the result, counting the separator. |
| **separator** | The text appended to a truncated string. This defaults to `...` |

```
{% set str = "This string is longer than we'd prefer" %}
{{str|truncate(11)}}
```

```
This str...
```

This filter takes (limit, separator). Twig's own **truncate** takes (length, preserve, separator), so a snippet copied from Twig's documentation passes its second argument as the _separator_, and no error is reported:

{{"The quick brown fox jumps over the lazy dog"|truncate(20, true)}} returns The quick brown fox1, using true as the separator text. Without it, truncate(20) returns The quick brown f...

## unescape

Decode HTML entities:

```
{{"&quot;iPhone&quot; is &copy; Apple, Inc."|unescape}}
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

## xml\_encode

Build XML from an array.

`|xml_encode(format)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `format` | Optional. When `true`, the output is indented. Defaults to `false`. |

**Returns:** The constructed XML as a string, or an empty string on failure.

**The array must have exactly one top-level key.** It becomes the root element, and any sibling keys beside it are **silently dropped** -- no error, no warning.

```
{% set data = {'name':'Acme','city':'Portland'} %}
{{data|xml_encode}}
```

```
<name>Acme</name>
```

`city` is gone. Wrap the whole thing in a single root key instead:

```
{% set data = {'org':{'name':'Acme','city':'Portland'}} %}
{{data|xml_encode}}
```

```
<org><name>Acme</name><city>Portland</city></org>
```

String keys become tag names. Integer keys – the elements of a list – become `<item>`:

```
{% set data = {'tickets':[{'mask':'ABC-1'},{'mask':'ABC-2'}]} %}
{{data|xml_encode}}
```

```
<tickets><item><mask>ABC-1</mask></item><item><mask>ABC-2</mask></item></tickets>
```

### Hints

Keys beginning with `@` are **hints rather than content**. They're skipped when walking children, and they're only read on integer-keyed entries – on a string-keyed entry the key itself is already the tag name, and both hints are ignored.

`@tag` renames those `<item>` elements:

```
{% set data = {'tickets':[
  {'@tag':'ticket','mask':'ABC-1'},
  {'@tag':'ticket','mask':'ABC-2'}
]} %}
{{data|xml_encode}}
```

```
<tickets><ticket><mask>ABC-1</mask></ticket><ticket><mask>ABC-2</mask></ticket></tickets>
```

`@attributes` sets attributes on that element, and a second argument of `true` indents the output:

```
{% set data = {'tickets':[
  {'@tag':'ticket','@attributes':{'id':'1','status':'open'},'mask':'ABC-1'}
]} %}
{{data|xml_encode(true)}}
```

```
<tickets>
  <ticket id="1" status="open">
    <mask>ABC-1</mask>
  </ticket>
</tickets>
```

Only `@tag` and `@attributes` are read. Other `@` keys are ignored rather than raising an error. Scalar values become text content, with carriage returns stripped.

There is also an [**xml\_encode** function](/docs/scripting/functions/#xml_encode), and it is a different function doing the opposite job: it _serializes_ an existing XML node back to a string. Passing an array to the function returns `false`, and piping a node into this filter won't serialize it.

[\< Functions](/docs/scripting/functions/)

[Tests \>](/docs/scripting/tests/)

# References

1. Wikipedia: Hash-based message authentication code (HMAC) - https://en.wikipedia.org/wiki/Hash-based\_message\_authentication\_code&nbsp;[↩](#fnref:hmac)

2. Wikipedia: Markdown - https://en.wikipedia.org/wiki/Markdown&nbsp;[↩](#fnref:markdown)

3. Wikipedia: MD5 - https://en.wikipedia.org/wiki/MD5&nbsp;[↩](#fnref:md5)

4. Wikipedia: Regular Expression - https://en.wikipedia.org/wiki/Regular\_expression&nbsp;[↩](#fnref:regexp)

5. Wikipedia: SHA-1 - https://en.wikipedia.org/wiki/SHA-1&nbsp;[↩](#fnref:sha1)

