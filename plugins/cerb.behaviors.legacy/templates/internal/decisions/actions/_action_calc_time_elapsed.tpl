<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">From date<span class="cerb-ui-form--hint">e.g. "Jan 1 2017 8am"</span></label>
	<input type="text" name="{$namePrefix}[date_from]" value="{$params.date_from}" class="placeholders" placeholder="">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">To date<span class="cerb-ui-form--hint">e.g. "Dec 31 2017 23:59"</span></label>
	<input type="text" name="{$namePrefix}[date_to]" value="{$params.date_to}" class="placeholders" placeholder="">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Determine availability from calendar</label>
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

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save time elapsed (seconds) to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[placeholder]" value="{$params.placeholder|default:"_time_elapsed"}" required="required" spellcheck="false" size="32" placeholder="e.g. _time_elapsed">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
