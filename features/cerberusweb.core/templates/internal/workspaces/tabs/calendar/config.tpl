<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Calendar</legend>

<b>Display:</b> 
<select name="params[calendar_id]">
	<option value="">-- {'common.choose'|devblocks_translate} --</option>
	{foreach from=$calendars item=calendar key=calendar_id}
	<option value="{$calendar_id}" {if $calendar_id==$workspace_tab->params.calendar_id}selected="selected"{/if}>{$calendar->name}</option>
	{/foreach}
</select>

<br>

</fieldset>

<script type="text/javascript">
$fieldset = $('#tabConfig{$workspace_tab->id}');
</script>