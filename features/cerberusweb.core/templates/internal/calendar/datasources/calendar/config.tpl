{$uniqid = uniqid()}
{$is_blank = empty($params.worklist_model.context)}

<div id="div{$uniqid}" class="datasource-params">
<b>Using</b>

<select name="params{$params_prefix}[sync_calendar_id]">
	<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
	{foreach from=$calendars item=sync_calendar}
	{if $params.sync_calendar_id==$sync_calendar->id || Context_Calendar::isReadableByActor($sync_calendar, $active_worker)}
	<option value="{$sync_calendar->id}" {if $params.sync_calendar_id==$sync_calendar->id}selected="selected"{/if}>{$sync_calendar->name}</option>
	{/if}
	{/foreach}
</select>

 <br>

</div>