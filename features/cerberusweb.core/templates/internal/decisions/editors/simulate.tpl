<form id="frmBehaviorSimulator{$trigger->id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="runSimulator">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}
<input type="hidden" name="event_params_json" value="{$event_params_json}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{* Target *}

{$ext_event->renderSimulatorTarget($trigger, $event_model)}

{* Parameters and Values *}

{$has_public_vars = false}
{foreach from=$trigger->variables item=var}
	{if !$var.is_private}{$has_public_vars = true}{/if}
{/foreach}

<div id="simulatorTabs">
	<ul>
		{if $has_public_vars}<li><a href="#simulatorParams">Parameters</a></li>{/if}
		<li><a href="#simulatorValues">Conditions</a></li>
	</ul>
	
	{if $has_public_vars}
	<div id="simulatorParams">
		{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$trigger->variables variable_values=$results field_name="values"}
	</div>
	{/if}
	
	<div id="simulatorValues">
		<div style="max-height:250px;overflow-y:auto;">
		<b>Current time</b>:
		<div style="margin:0px 0px 2px 10px;">
			<input type="text" name="values[_current_time]" value="now" size="45" style="width:98%;">
		</div>
		
		{foreach from=$dictionary item=v key=k}
			{if $has_public_vars && isset($trigger->variables[$k]) && !$trigger->variables[$k]['is_private']}
			{else}
			<b>{$v.label}</b>
			<div style="margin:0px 0px 2px 10px;">
				{if $v.type == 'T'}
				<textarea name="values[{$k}]" cols="45" rows="8" style="width:98%;height:75px;">{$v.value}</textarea>
				{elseif $v.type == 'C'}
				<label><input type="radio" name="values[{$k}]" value="1" {if !empty($v.value)}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="values[{$k}]" value="0" {if empty($v.value)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
				{elseif $v.type == 'E'}
				<input type="text" name="values[{$k}]" value="{$v.value|devblocks_date}" size="45" style="width:98%;">
				{else}
				<input type="text" name="values[{$k}]" value="{$v.value}" size="45" style="width:98%;">
				{/if}
			</div>
			{/if}
		{/foreach}
		</div>
	</div>
</div>

<div style="margin-top:15px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-cogwheel"></span> Simulate</button>
</div>

<div id="divBehaviorSimulatorResults{$trigger->id}" style="padding:5px;"></div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('simulate_behavior');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Simulate: {$trigger->title|escape:'javascript' nofilter}");
		
		$popup.find('button.submit').click(function() {
			var $button = $(this).hide();
			var $output = $('#divBehaviorSimulatorResults{$trigger->id}').html('<span class="cerb-ajax-spinner"></span>');
			
			genericAjaxPost('frmBehaviorSimulator{$trigger->id}',$output,null, function() {
				$button.show();
			});
		});
		
		$('#simulatorTabs').tabs();
	});
});
</script>