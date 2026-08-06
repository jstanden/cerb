<form id="frmBehaviorSimulator{$trigger->id}" class="cerb-ui-form">
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

{$has_public_vars = $trigger->hasPublicVariables()}

<div id="simulatorTabs{$trigger->id}">
	<ul>
		{if $has_public_vars}<li><a href="#simulatorParams{$trigger->id}">Parameters</a></li>{/if}
		<li><a href="#simulatorValues{$trigger->id}">Conditions</a></li>
	</ul>

	{if $has_public_vars}
	<div id="simulatorParams{$trigger->id}">
		{include file="devblocks:cerb.behaviors.legacy::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$trigger->variables variable_values=$results field_name="values"}
	</div>
	{/if}

	<div id="simulatorValues{$trigger->id}">
		<div style="max-height:250px;overflow-y:auto;">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Current time</label>
			<input type="text" name="values[_current_time]" value="now">
		</div>

		{foreach from=$dictionary item=v key=k}
			{if $has_public_vars && isset($trigger->variables[$k]) && !$trigger->variables[$k]['is_private']}
			{else}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{$v.label}</label>
				{if $v.type == 'T'}
				<textarea name="values[{$k}]" rows="8">{$v.value}</textarea>
				{elseif $v.type == 'C'}
				<input type="hidden" name="values[{$k}]" value="{if !empty($v.value)}1{else}0{/if}">
				<div>
					<div class="cerb-ui-switcher cerb-behavior-sim-switcher">
						<button type="button" data-value="1"{if !empty($v.value)} class="cerb-ui-switcher--active"{/if}>{'common.yes'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="0"{if empty($v.value)} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
					</div>
				</div>
				{elseif $v.type == 'E'}
				<input type="text" name="values[{$k}]" value="{$v.value|devblocks_date}">
				{else}
				<input type="text" name="values[{$k}]" value="{$v.value}">
				{/if}
			</div>
			{/if}
		{/foreach}
		</div>
	</div>
</div>

<div class="cerb-u-mt-3">
	<button type="button" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-gear cerb-u-anim-pulse-hover"></span> Simulate</button>
</div>

<div id="divBehaviorSimulatorResults{$trigger->id}" style="padding:5px;"></div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('simulate_behavior');
	let $frm = $('#frmBehaviorSimulator{$trigger->id}');

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"Simulate: {$trigger->title|escape:'javascript' nofilter}");

		if(window.CerbUI && CerbUI.Switcher) {
			$frm.find('.cerb-behavior-sim-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[type=hidden]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$frm.find('button.cerb-ui-button').click(function() {
			let $button = $(this).hide();
			let $output = $('#divBehaviorSimulatorResults{$trigger->id}').empty().append(Devblocks.getSpinner());

			genericAjaxPost($frm, $output, null, function() {
				$button.show();
			});
		});

		$('#simulatorTabs{$trigger->id} > ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });
	});
});
</script>
