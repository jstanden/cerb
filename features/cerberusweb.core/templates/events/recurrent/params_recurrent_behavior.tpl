{$fieldset_id = uniqid()}
<fieldset id="{$fieldset_id}" style="margin-top:5px;">
	<b>Repeat every:</b>
	<div style="margin-left:10px;margin-bottom:5px;">
		<textarea name="event_params[repeat_patterns]" data-editor-mode="ace/mode/ini" rows="6" cols="45" style="height:8em;width:100%;" placeholder="Enter any number of cron expressions">{$trigger->event_params.repeat_patterns}</textarea>
		<select class="cerb-insert-menu">
			<option value="">-- {'common.examples'|devblocks_translate|lower} --</option>
			<optgroup label="Time intervals">
				<option value="# Every 5 mins\n*/5 * * * *">5 mins</option>
				<option value="# Every 30 mins\n*/30 * * * *">30 mins</option>
				<option value="# Every hour\n0 * * * *">1 hour</option>
				<option value="# At 8am every day\n0 8 * * *">8am</option>
				<option value="# At 5pm every day\n0 17 * * *">5pm</option>
			</optgroup>
			<optgroup label="Days of the week">
				<option value="# Once every weekday\n0 0 * * 1-5">Weekdays</option>
				<option value="# Once every weekend day\n0 0 * * 6-7">Weekends</option>
				<option value="# Once every Sunday\n0 0 * * 7">Sunday</option>
				<option value="# Once every Monday\n0 0 * * 1">Monday</option>
				<option value="# Once every Tuesday\n0 0 * * 2">Tuesday</option>
				<option value="# Once every Wednesday\n0 0 * * 3">Wednesday</option>
				<option value="# Once every Thursday\n0 0 * * 4">Thursday</option>
				<option value="# Once every Friday\n0 0 * * 5">Friday</option>
				<option value="# Once every Saturday\r\n0 0 * * 6">Saturday</option>
			</optgroup>
			<optgroup label="Days of the month">
				<option value="# First day of every month\n0 0 1 * *">1st</option>
				<option value="# 15th day of every month\n0 0 15 * *">15th</option>
				<option value="# Last day of every month\n0 0 L * *">Last day of every month</option>
			</optgroup>
			<optgroup label="Days of the year">
				<option value="# Jan 1\n0 0 1 1 *">Jan 1</option>
				<option value="# Dec 25\n0 0 25 12 *">Dec 25</option>
			</optgroup>
			<optgroup label="Specific weekdays">
				<option value="# Last Thursday of November\n0 0 22-28 11 4">Last Thursday of November</option>
			</optgroup>
		</select>
	</div>
	
	<b>Timezone:</b>
	<div style="margin-left:10px;margin-bottom:5px;">
		<select name="event_params[timezone]">
			{foreach from=$timezones item=timezone}
			<option value="{$timezone}" {if $timezone == $trigger->event_params.timezone}selected="selected"{/if}>{$timezone}</option>
			{/foreach}
		</select>
	</div>
	
	{if $trigger->event_params.repeat_run_at}
	{$run_at = $trigger->event_params.repeat_run_at}
	<b>Next run:</b>
	<div style="margin-left:10px;margin-bottom:5px;">
	{if $run_at < time()}
	<abbr title="{$run_at|devblocks_date}">running now</abbr>
	{else}
	<abbr title="{$run_at|devblocks_date}">{$run_at|devblocks_prettytime}</abbr>
	{/if}
	</div>
	{/if}
	
	{if is_array($trigger->event_params.repeat_history)}
	<b>Run history:</b>
	<div style="margin-left:10px;margin-bottom:5px;">
		{$history = $trigger->event_params.repeat_history|array_reverse}
		{section loop=$history name=ts max=5}
		{$history[ts]|devblocks_prettytime}{if !$smarty.section.ts.last}, {/if}
		{/section}
	</div>
	{/if}
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	var $textarea = $fieldset.find('textarea:first');
	var $select = $fieldset.find('select.cerb-insert-menu');
	
	$textarea
		.cerbCodeEditor()
	;
	
	$select.on('change', function(e) {
		e.stopPropagation();
		
		var $field = $select.prevAll('pre.ace_editor, :text, textarea').first();
		
		var lines = $select.val().split('\\n');
		
		for(var idx in lines) {
			var line = lines[idx];
			if($field.is(':text, textarea')) {
				$field.focus().insertAtCursor(line + '\n');
				
			} else if($field.is('.ace_editor')) {
				var evt = new jQuery.Event('cerb.insertAtCursor');
				evt.content = line + '\n';
				$field.trigger(evt);
			}
		}
		
		$select.val('');
	});
});
</script>