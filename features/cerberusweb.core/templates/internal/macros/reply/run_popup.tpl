<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmRunBehaviorPopup">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="getMacroReply">
<input type="hidden" name="macro" value="{$macro->id}">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="message_id" value="{$message_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

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
<fieldset class="peek black">
	<legend>Parameters</legend>
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$macro->variables variable_values=[] field_name="var_vals"}
</fieldset>
{/if}

<div class="toolbar">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.ok'|devblocks_translate}</button>
	<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$macro->id}&context={$context}&context_id={$context_id}','reuse',false,'50%');"> <span class="glyphicons glyphicons-cogwheel"></span> Simulator</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#frmRunBehaviorPopup');
	
	$popup.one('popup_open', function(event,ui) {
		var $frm = $popup.find('form');
		
		$popup.dialog('option','title',"Perform Behavior");
		
		{if $has_variables}
		$popup.find('input:text').first().select();
		{else}
		$popup.find('button').first().focus();
		{/if}
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('frmRunBehaviorPopup', '', null, function(js) {
				$('<div></div>').html(js).appendTo($('body'));
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>