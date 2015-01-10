<h1 style="margin-bottom:10px;color:inherit;">Placeholders</h1>

<fieldset class="peek">
	<legend>Placeholders</legend>
	
	{literal}
	<p>
		A <b><tt>{{placeholder}}</tt></b> will be automatically replaced with a fragment of text from a record. 
		Placeholders are surrounded by two pairs of curly braces.
	</p>
	{/literal}

	<p>
		For example, the placeholder text "Hi {literal}<b><tt>{{first_name}}</tt></b>{/literal}" would become "Hi {$active_worker->first_name}".
	</p>
</fieldset>

<fieldset class="peek">
	<legend>Filters</legend>
	
	{literal}
	<p>
		The value of a placeholder may be modified using <i>filters</i>. 
		Some filters also have parameters that modify their behavior, which are provided in parentheses.
		The possible filters will be automatically suggested when you append the pipe character (<b><tt>|</tt></b>) to a placeholder name. 
	</p>
	{/literal}
	
	<p>
		For example, "Hi {literal}<b><tt>{{first_name|upper}}</tt></b>{/literal}" would become "Hi {$active_worker->first_name|upper}".
	</p>
</fieldset>

<fieldset class="peek">
	<legend>Default values</legend>
	
	<p>
		You can use the <b><tt>|default</tt></b> filter to give a default value to empty placeholders.
	</p>
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set name = '' %}
Hi {{name|default('there')}}
</pre>
{/literal}
</fieldset>

<h1 style="margin-bottom:10px;color:inherit;">Scripting</h1>

<fieldset class="peek">
	<legend>Variables</legend>
	
	You can set temporary variables and use them as placeholders:
	
	<pre style="margin:0.5em 1em;">
{literal}{%{/literal} set name = '{$active_worker->first_name}' {literal}%}{/literal}
{literal}{{name}}{/literal}</pre>
</fieldset>

<fieldset class="peek">
	<legend>Arrays and objects</legend>
	
	{literal}
	<pre style="margin:0.5em 1em;">
{# Associative array #}
{% set var = <b>{"first_name": "William", "last_name":"Portcullis"}</b> %}
{{var.first_name}} {{var.last_name}}

{# Iterative arrays #}
{% set var = <b>['red','green','blue']</b> %}
{{var.1}} or {{var[2]}}

{# Dynamic indices #}
{% set var = ['one','two','three'] %}
{% set idx = 2 %}
<b>{{attribute(var,idx-1)}}</b> is after <b>{{var[idx-2]}}</b> and before <b>{{var[idx]}}</b>
</pre>
	{/literal}
</fieldset>

<fieldset class="peek">
	<legend>Modifying objects and arrays</legend>
	
	{literal}
	<pre style="margin:0.5em 1em;">
{# Mixed objects and arrays #}
{% set var = {"name":"..."} %}
{% set var = <b>dict_set</b>(var, 'name', {}) %}
{% set var = <b>dict_set</b>(var, 'name.first', 'Kina') %}
{% set var = <b>dict_set</b>(var, 'name.last', 'Halpue') %}
{% set var = <b>dict_set</b>(var, 'title', 'Support Master') %}
{% set var = <b>dict_set</b>(var, 'skills.[]', 'PHP') %}
{% set var = <b>dict_set</b>(var, 'skills.[]', 'MySQL') %}
{{var|json_encode|json_pretty}}

{# Assoc arrays #}
{% set var = {"group": {}} %}
{% set var = <b>dict_set</b>(var, 'group.name', 'Support') %}
{% set var = <b>dict_set</b>(var, 'group.members.[]', 'Kina Halpue') %}
{% set var = <b>dict_set</b>(var, 'group.members.[]', 'William Portcullis') %}
{% set var = <b>dict_set</b>(var, 'group.members.[]', 'Steven Emplois') %}
{{var|json_encode|json_pretty}}

{# Iterative arrays #}
{% set var = [1,2,[3,4,[5,6]]] %}
{% set var = <b>dict_set</b>(var, '2.2.[]', 7) %}
{% set var = <b>dict_set</b>(var, '2.2.[]', 8) %}
{% set var = <b>dict_set</b>(var, '2.3', 9) %}
{{var|json_encode|json_pretty}}	
</pre>
	{/literal}
</fieldset>

<fieldset class="peek">
	<legend>Loops</legend>
	
	If a placeholder value is a list (array), then it can be iterated in a loop:
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set list_of_names = ["Jeff", "Dan", "Darren"] %}
{% for name in list_of_names %}
{{name}}
{% endfor %}
</pre>
	{/literal}
	
</fieldset>

<fieldset class="peek">
	<legend>Conditional Logic</legend>
	
	Conditional logic can display different content based on the value of a placeholder:
	
	{literal}
	<pre style="margin:0.5em 1em;">
{% set sla_expiration = '+2 weeks'|date('U') %}
<b>{% if sla_expiration >= 'now'|date('U') %}</b>
Your SLA coverage is active.
<b>{% else %}</b>
Your SLA coverage has expired.
<b>{% endif %}</b>
</pre>
	{/literal}
</fieldset>

{literal}
<fieldset class="peek">
	<legend>Operators</legend>
	
	<pre style="margin:0.5em 1em;">
{% set this = 0 %}
{% set that = 1 %}
{% set those = [1,2,3] %}

{% if this == that %}
{{this}} equals {{that}}
{% endif -%}

{% if this != that %}
{{this}} doesn't equal {{that}}
{% endif -%}

{% if this < that %}
{{this}} is less than {{that}}
{% endif -%}

{% if this > that %}
{{this}} is greater than {{that}}
{% endif -%}

{% if that in those %}
{{that}} is in {{those|join(',')}}
{% endif -%}

{% if this not in those %}
{{this}} is not in {{those|join(',')}}
{% endif -%}</pre>

</fieldset>
{/literal}

<fieldset class="peek">
	<legend>Concatenation</legend>
	
	<pre style="margin:0.5em 1em;">
{literal}{%{/literal} set first_name = "{$active_worker->first_name}" {literal}%}{/literal}
{literal}{%{/literal} set last_name = "{$active_worker->last_name}" {literal}%}{/literal}
{literal}{% set full_name = first_name ~ ' ' ~ last_name %}{/literal}
{literal}{{full_name}}{/literal}
</pre>

</fieldset>

{literal}
<fieldset class="peek">
	<legend>Whitespace</legend>

	You can ignore whitespace at the beginning or end of a tag with a dash (<tt><b>-</b></tt>):
	
	<pre style="margin:0.5em 1em;">
This text

{{-" has no leading or trailing whitespace "-}}

in it.
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>Regular Expressions</legend>
	
	<pre style="margin:0.5em 1em;">
{% set text = "Your Amazon Order #Z-1234-5678-9 has shipped!" %}
{% set order_id = text|regexp("/Amazon Order #([A-Z0-9\-]+)/", 1) %}
Amazon Order #: {{order_id}}</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>Date/Time</legend>
	
	<pre style="margin:0.5em 1em;">
{{'now'|date('F d, Y h:ia T')}}
{{'tomorrow 5pm'|date('D, d F Y H:i T')}}
{{'+2 weeks 08:00'|date('Y-m-d h:ia T')}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>Date/Time Manipulation</legend>
	
	<pre style="margin:0.5em 1em;">
{% set timestamp = date('now')|date_modify('-2 days') %}
{{timestamp|date('D, d M Y T')}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>Timezones</legend>
	
	<pre style="margin:0.5em 1em;">
{% set time_format = 'D, d M Y H:i T' %}

{#- Set the timezone in the date variable -#}

{% set ts_london = date('now', 'Europe/London') %}
{% set ts_losangeles = date('now', 'America/Los_Angeles') %}
{% set ts_tokyo = date('now', 'Asia/Tokyo') -%}

London: {{ts_london|date(time_format, false)}}
Los Angeles: {{ts_losangeles|date(time_format, false)}}
Tokyo: {{ts_tokyo|date(time_format, false)}}

{# Set the timezone in the date filter -#}

{% set ts_now = date() -%}

Bangalore: {{ts_now|date(time_format, 'Asia/Calcutta')}}
Berlin: {{ts_now|date(time_format, 'Europe/Berlin')}}
New York: {{ts_now|date(time_format, 'America/New_York')}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>JSON Decoding</legend>
	
	<pre style="margin:0.5em 1em;">
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
Customer: {{json.name}}
Order #: {{json.order_id}}	
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>JSON Modification</legend>
	
	<pre style="margin:0.5em 1em;">
{% set json = {'name': 'Joe Customer', 'order_id': 12345} %}
{% set json = jsonpath_set(json, 'order_id', 54321) %}
{% set json = jsonpath_set(json, 'status.text', 'shipped') %}
{% set json = jsonpath_set(json, 'status.tracking_id', 'Z1F238') %}
Customer: {{json.name}}
Order #: {{json.order_id}}
Status: {{json.status.text}}
Tracking #: {{json.status.tracking_id}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>JSON Encoding</legend>
	
	<pre style="margin:0.5em 1em;">
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json<b>|json_encode</b>}}	
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>JSON Prettification</legend>
	
	<pre style="margin:0.5em 1em;">
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode<b>|json_pretty}}</b>
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>XML Decoding</legend>
	
	<pre style="margin:0.5em 1em;">
{% set string_of_xml = 
"&lt;response&gt;
  &lt;client_id&gt;1&lt;/client_id&gt;
  &lt;invoice_id&gt;123&lt;/invoice_id&gt;
&lt;/response&gt;"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set client_id = xml_xpath(xml, '//client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>XML Encoding</legend>
	
	<pre style="margin:0.5em 1em;">
{% set string_of_xml = 
"&lt;response xmlns=\"http://www.example.com/api/\"&gt;
  &lt;client_id&gt;1&lt;/client_id&gt;
  &lt;invoice_id&gt;123&lt;/invoice_id&gt;
&lt;/response&gt;"
-%}
{% set xml = xml_decode(string_of_xml) %}
{{xml_encode(xml.client_id)}}	
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>XML Namespaces</legend>
	
	<pre style="margin:0.5em 1em;">
{% set string_of_xml = 
"&lt;response xmlns=\"http://www.example.com/api/\"&gt;
  &lt;client_id&gt;1&lt;/client_id&gt;
  &lt;invoice_id&gt;123&lt;/invoice_id&gt;
&lt;/response&gt;"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set xml = xml_xpath_ns(xml, 'ns', 'http://www.example.com/api/') %}
{% set client_id = xml_xpath(xml, '//ns:client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//ns:invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
</pre>
</fieldset>
{/literal}

{literal}
<fieldset class="peek">
	<legend>Natural Language Processing</legend>
	
	<pre style="margin:0.5em 1em;">
{% set patterns = [
  "Remind me about [what] [when]", 
  "Remind me to [what] [when]",
  "Remind me [what] [when]"
] %}
{% set json = json_decode(
  "remind me to run server maint tomorrow at 10am"|nlp_parse(patterns)
) %}
What: {{json.what|first}}
When: {{json.when|first}}
</pre>
</fieldset>
{/literal}

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('help');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Help: Placeholders \x26 Scripting");
	});
</script>
