{if empty($job)}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmScheduledBehaviorPeek">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="applyMacro">
<input type="hidden" name="macro" value="{$macro->id}">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
<input type="hidden" name="return_url" value="{$return_url}">
{else}
<form action="#" method="post" id="frmScheduledBehaviorPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveMacroSchedulerPopup">
<input type="hidden" name="job_id" value="{$job->id}">
{/if}

<b>Behavior:</b><br>
{if !empty($macro->title)}
	{$macro->title}
{else}
	{$event = DevblocksPlatform::getExtension($macro->event_point, false)}
	{$event->name}
{/if}	
<br>
<br>

<b>When should this behavior happen?</b>
<div>
	{if !empty($dates)}
	<select name="run_relative">
		<option value="">- at this date &amp; time: -</option>
		{foreach from=$dates item=date key=k}
		<option value="{$k}" {if $job->run_relative==$k}selected="selected"{/if}>{$date.label}</option>
		{/foreach}
	</select>
	{/if}
	{if empty($job)}
		<input type="text" name="run_date" size="32" value="now" placeholder="+2 hours; tomorrow 5pm"><br>
	{else}
		{if $editable}
			<input type="text" name="run_date" size="32" value="{if !empty($job->run_relative)}{$job->run_literal}{else}{$job->run_date|devblocks_date}{/if}" placeholder="+2 hours; tomorrow 5pm"><br>
		{else}
			{$job->run_date|devblocks_date}<br>
		{/if}
	{/if}
</div>
<div>
	<i>e.g. now; +2 days; Monday; tomorrow 8am; 5:30pm; May 26</i>
</div>
<br>

{* Custom variables *}
{$has_variables = false}
{foreach from=$macro->variables item=var}
	{if empty($var.is_private)}{$has_variables = true}{/if}
{/foreach}

{if $has_variables}
<fieldset>
	<legend>Parameters</legend>
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$macro->variables variable_values=$job->variables field_name="var_vals"}
</fieldset>
{/if}

<table>
	<tbody class="repeat">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"> Repeat:</td>
		<td width="100%">
			<label><input type="radio" name="repeat_freq" value="" {if empty($job->repeat.freq)}checked="checked"{/if}> Never</label>
			<label><input type="radio" name="repeat_freq" value="interval" {if $job->repeat.freq=='interval'}checked="checked"{/if}> Every</label>
			<label><input type="radio" name="repeat_freq" value="weekly" {if $job->repeat.freq=='weekly'}checked="checked"{/if}> Weekly</label>
			<label><input type="radio" name="repeat_freq" value="monthly" {if $job->repeat.freq=='monthly'}checked="checked"{/if}> Monthly</label>
			<label><input type="radio" name="repeat_freq" value="yearly" {if $job->repeat.freq=='yearly'}checked="checked"{/if}> Yearly</label>
			
			<div class="terms" style="padding-top:5px;">
				<div class="interval" style="display:{if $job->repeat.freq=='interval'}block{else}none{/if};">
					<fieldset>
						<input type="text" name="repeat_options[interval][every_n]" value="{if $job->repeat.freq == 'interval' && isset($job->repeat.options.every_n)}{$job->repeat.options.every_n}{else}2 hours{/if}" size="24"><br>
						<i>(e.g. 5 minutes; 2 hours; 1 day)</i> 
					</fieldset>
				</div>
				<div class="weekly" style="display:{if $job->repeat.freq=='weekly'}block{else}none{/if};">
					<fieldset>
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							<tr>
							{$day_time = strtotime("last Sunday")}
							{section loop=7 start=0 name=days}
								{$sel = $job->repeat.freq == 'weekly' && false !== in_array({$smarty.section.days.index}, $job->repeat.options.day)}
								<td {if $sel}class="selected"{/if}>
									<input type="checkbox" name="repeat_options[weekly][day][]" value="{$smarty.section.days.index}" {if $sel}checked="checked"{/if}>
									{$day_time|devblocks_date:'D'}
								</td>
								{$day_time = strtotime("tomorrow", $day_time)}
							{/section}
							</tr>
						</table>
						
					</fieldset>
				</div>
				<div class="monthly" style="display:{if $job->repeat.freq=='monthly'}block{else}none{/if};">
					<fieldset>
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							{section loop=32 start=1 name=days}
								{$sel = $job->repeat.freq == 'monthly' && false !== in_array({$smarty.section.days.index}, $job->repeat.options.day)}
								{if $smarty.section.days.iteration % 7 == 1}
									<tr>
								{/if}
									<td {if $sel}class="selected"{/if}>
										<input type="checkbox" name="repeat_options[monthly][day][]" value="{$smarty.section.days.iteration}" {if $sel}checked="checked"{/if}>
										{$smarty.section.days.iteration}
									</td>
								{if $smarty.section.days.last || $smarty.section.days.iteration % 7 == 0}
									</tr>
								{/if}
							{/section}
						</table>
					</fieldset>
				</div>
				<div class="yearly" style="display:{if $job->repeat.freq=='yearly'}block{else}none{/if};">
					<fieldset>
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							{section loop=13 start=1 name=months}
								{$month_time = mktime(0,0,0,$smarty.section.months.iteration,1,0)}
								{$sel = $job->repeat.freq == 'yearly' && false !== in_array({$smarty.section.months.index}, $job->repeat.options.month)}
								{if $smarty.section.months.iteration % 4 == 1}
									<tr>
								{/if}
								<td {if $sel}class="selected"{/if}>
									<input type="checkbox" name="repeat_options[yearly][month][]" value="{$smarty.section.months.iteration}" {if $sel}checked="checked"{/if}>
									{$month_time|devblocks_date:'M'}
								</td>
								{if $smarty.section.months.last || $smarty.section.months.iteration % 4 == 0}
									</tr>
								{/if}
							{/section}
						</table>
					</fieldset>
				</div>
				
			</div>
		</td>
	</tr>
	</tbody>
	<tbody class="end" style="{if empty($job) || !isset($job->repeat.freq)}display:none;{/if}">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"> End:</td>
			<td width="100%">
				<label><input type="radio" name="repeat_end" value="" {if empty($job->repeat.end.term)}checked="checked"{/if}> Never</label>
				<label><input type="radio" name="repeat_end" value="date" {if $job->repeat.end.term=='date'}checked="checked"{/if}> On Date</label>
				
				<div class="ends">
					<div class="end date" style="display:{if $job->repeat.end.term=='date'}block{else}none{/if};">
						<fieldset>
							<input type="text" name="repeat_ends[date][on]" value="{if $job->repeat.end.term=='date' && isset($job->repeat.end.options.on)}{$job->repeat.end.options.on|devblocks_date:'M d Y h:ia'}{/if}" style="width:98%;"> 
						</fieldset>
					</div>
				</div>
			</td>
		</tr>
	</tbody>
</table>

{if !empty($job) && $editable}
<fieldset class="delete" style="display:none;">
	<input type="hidden" name="do_delete" value="0">
	<legend>Delete this scheduled behavior?</legend>
	<p>Are you sure you want to permanently delete this behavior?</p>
	<button type="button" class="green" onclick="$(this).closest('fieldset').find('input:hidden[name=do_delete]').val('1');$(this).closest('fieldset').next('div.toolbar').find('button.save').click();"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="toolbar">
	{if empty($job)}
		<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate}</button>
		<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$macro->id}&context={$context}&context_id={$context_id}','reuse',false,'500');"> <span class="cerb-sprite2 sprite-gear"></span> Simulator</button>
	{else}
		{if $editable}
			<button type="button" class="save" onclick="genericAjaxPopupPostCloseReloadView(null,'frmScheduledBehaviorPeek', null, false, 'behavior_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate}</button>
			<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$job->behavior_id}&context={$job->context}&context_id={$job->context_id}','reuse',false,'500');"> <span class="cerb-sprite2 sprite-gear"></span> Simulator</button>
			<button type="button" class="delete" onclick="$(this).closest('div').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
		{else}
			<button type="button" onclick="genericAjaxPopupDestroy('peek');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate}</button>
		{/if}
	{/if}
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$this = $(this);
		$frm = $this.find('form');
		
		$this.dialog('option','title',"Schedule Behavior");
		$this.find('input:text').first().select();
		
		// Repeat freq
		
		$frm.find('input:radio[name=repeat_freq]').click(function(e) {
			$td = $(this).closest('td');
			$table = $td.closest('table');
			$terms = $td.find('div.terms');
			$val = $(this).val();
			
			$terms.find('> div').hide();

			if($val.length > 0) {
				$terms.find('div.'+$(this).val()).fadeIn();
				$table.find('tbody.end').show();
			} else {
				$table.find('tbody.end').hide();
			}
		});
		
		// Repeat end
		
		$frm.find('input:radio[name=repeat_end]').click(function(e) {
			$ends=$(this).closest('td').find('div.ends');
			$val = $(this).val();
			
			$ends.find('> div').hide();

			if($val.length > 0) {
				$ends.find('div.'+$(this).val()).fadeIn();
			}
		});

		// Modify recurring event
		
		$frm.find('DIV.buttons INPUT:radio[name=edit_scope]').change(function(e) {
			$frm = $(this).closest('form');
			$val = $(this).val();
			
			if($val == 'this') {
				$frm.find('tbody.repeat, tbody.end').hide();
			} else {
				$frm.find('tbody.repeat, tbody.end').show();
			}
		});	
		
		// Toggle grids
		
		$frm.find('TABLE.toggle_grid TR TD').click(function(e) {
			$td = $(this).closest('td');
			$td.disableSelection();
			
			if($td.is('.selected')) {
				$td.find('input:checkbox').removeAttr('checked');
			} else {
				$td.find('input:checkbox').attr('checked', 'checked');
			}
			
			$td.toggleClass('selected');
			
			e.stopPropagation();
		});
		
	});
</script>
