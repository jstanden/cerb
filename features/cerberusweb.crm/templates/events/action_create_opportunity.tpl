<b>{'common.name'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[name]" value="{$params.name}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="">
</div>

<b>{'common.email'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[email]" value="{$params.email}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="user@example.com">
</div>

<b>{'common.status'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[status]">
		<option value="open" {if $params.status=="open"}selected="selected"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</option>
		<option value="closed_won" {if $params.status=="closed_won"}selected="selected"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</option>
		<option value="closed_lost" {if $params.status=="closed_lost"}selected="selected"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</option>
	</select>
</div>

<b>{'crm.opportunity.amount'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[amount]" value="{$params.amount}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. 1500.00">
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}:</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false field_wrapper="{$namePrefix}"}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY field_wrapper="{$namePrefix}"}

<b>{'common.comment'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;" class="placeholders">{$params.comment}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

{if !empty($values_to_contexts)}
<b>Link to:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}

<b>Also create records in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

{* Check for object variables *}
{capture name="object_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_OPPORTUNITY}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.object_vars}
<b>Add object to list variable:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.object_vars nofilter}
	</select>
</div>
{/if}

{*
<script type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');
</script>
*}