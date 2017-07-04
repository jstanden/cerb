<b>From date:</b> (e.g. "Jan 1 2017 8am")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[date_from]" style="width:100%;" value="{$params.date_from}" class="placeholders" placeholder="">
</div>

<b>To date:</b> (e.g. "Dec 31 2017 23:59")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[date_to]" style="width:100%;" value="{$params.date_to}" class="placeholders" placeholder="">
</div>

<b>Determine availability from calendar:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[calendar_id]">
		<option value=""></option>
		{foreach from=$values_to_contexts key=var_key item=var}
		{if $var.context == CerberusContexts::CONTEXT_CALENDAR}
		<option value="{$var_key}" {if $params.calendar_id == $var_key}selected="selected"{/if}>({'common.variable'|devblocks_translate|capitalize}) {$var.label}</option>
		{/if}
		{/foreach}
		{foreach from=$calendars item=calendar}
		<option value="{$calendar->id}" {if $params.calendar_id == $calendar->id}selected="selected"{/if}>{$calendar->name}</option>
		{/foreach}
	</select>
</div>

<b>Save time elapsed (seconds) to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[placeholder]" value="{$params.placeholder|default:"_time_elapsed"}" required="required" spellcheck="false" size="32" placeholder="e.g. _time_elapsed">&#125;&#125;
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
});
</script>