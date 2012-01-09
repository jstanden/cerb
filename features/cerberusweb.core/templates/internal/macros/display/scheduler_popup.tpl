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

<b>When should this behavior happen?</b><br>
{if empty($job)}
	<input type="text" name="run_date" size="32" value="now" style="width:98%;"><br>
	<i>e.g. now; +2 days; Monday; tomorrow 8am; 5:30pm; May 26</i><br>
{else}
	{if $editable}
		<input type="text" name="run_date" size="32" value="{$job->run_date|devblocks_date}" style="width:98%;"><br>
		<i>e.g. now; +2 days; Monday; tomorrow 8am; 5:30pm; May 26</i><br>
	{else}
		{$job->run_date|devblocks_date}<br>
	{/if}
{/if}
<br>

{* Custom variables *}
{$has_variables = false}
{foreach from=$macro->variables item=var}
	{if empty($var.is_private)}{$has_variables = true}{/if}
{/foreach}

{if $has_variables}
<fieldset>
	<legend>Parameters</legend>
	{foreach from=$macro->variables key=var_key item=var}
		{if empty($var.is_private)}
		<div>
			<input type="hidden" name="var_keys[]" value="{$var.key}">
			<b>{$var.label}:</b><br>
			{if $var.type == 'S'}
			<input type="text" name="var_vals[]" value="{$job->variables.$var_key}" style="width:98%;">
			{elseif $var.type == 'N'}
			<input type="text" name="var_vals[]" value="{$job->variables.$var_key}">
			{elseif $var.type == 'C'}
			<label><input type="radio" name="var_vals[]" value="1" {if $job->variables.$var_key}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label> 
			<label><input type="radio" name="var_vals[]" value="0" {if !$job->variables.$var_key}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label> 
			{elseif $var.type == 'E'}
			<input type="text" name="var_vals[]" value="{$job->variables.$var_key}" style="width:98%;">
			{elseif $var.type == 'W'}
			{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
			<select name="var_vals[]">
				<option value=""></option>
				{foreach from=$workers item=worker}
				<option value="{$worker->id}" {if $job->variables.$var_key==$worker->id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			{/if}
		</div>
		{/if}
	{/foreach}
</fieldset>
{/if}

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
	<button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.ok'|devblocks_translate}</button>
	{else}
		{if $editable}
			<button type="button" class="save" onclick="genericAjaxPopupPostCloseReloadView(null,'frmScheduledBehaviorPeek', null, false, 'behavior_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.ok'|devblocks_translate}</button>
			<button type="button" class="delete" onclick="$(this).closest('div').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>
		{else}
			<button type="button" onclick="genericAjaxPopupDestroy('peek');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.ok'|devblocks_translate}</button>
		{/if}
	{/if}
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Schedule Behavior");
		$(this).find('input:text').first().select();
	});
</script>
