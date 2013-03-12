<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmRunBehaviorPopup">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="getMacroReply">
<input type="hidden" name="macro" value="{$macro->id}">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="message_id" value="{$message_id}">

<b>Behavior:</b><br>
{if !empty($macro->title)}
	{$macro->title}
{else}
	{$event = DevblocksPlatform::getExtension($macro->event_point, false)}
	{$event->name}
{/if}
<br>
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

<div class="toolbar">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate}</button>
	<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$macro->id}&context={$context}&context_id={$context_id}','reuse',false,'500');"> <span class="cerb-sprite2 sprite-gear"></span> Simulator</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		var $frm = $this.find('form');
		
		$this.dialog('option','title',"Perform Behavior");
		
		{if $has_variables}
		$this.find('input:text').first().select();
		{else}
		$this.find('button').first().focus();
		{/if}
		
		$this.find('button.submit').click(function() {
			genericAjaxPost('frmRunBehaviorPopup', '', null, function(js) {
				$('<div></div>').html(js).appendTo($('body'));
				genericAjaxPopupClose('peek');
			});
		});
	});
</script>
