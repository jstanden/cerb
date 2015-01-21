<b>{'common.calendar'|devblocks_translate|capitalize}</b>:
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[calendar_id]">
	{foreach from=$trigger->variables item=var key=var_key}
	{if $var.type == "ctx_{CerberusContexts::CONTEXT_CALENDAR}"}
	<option value="{$var_key}" {if $params.calendar_id==$var_key}selected="selected"{/if}>(variable) {$var.label}</option>
	{/if}
	{/foreach}

	{foreach from=$calendars item=calendar key=calendar_id}
	<option value="{$calendar_id}" {if $params.calendar_id==$calendar_id}selected="selected"{/if}>{$calendar->name}</option>
	{/foreach}
</select>
</div>

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[title]" size="45" value="{$params.title}" style="width:100%;" class="placeholders">
</div>

<b>When:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[when]" size="45" value="{$params.when}" class="input_date placeholders">
</div>

<b>Until:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[until]" size="45" value="{$params.until}" class="input_date placeholders">
</div>

<b>{'common.status'|devblocks_translate|capitalize}:</b>
{* [TODO] Text *}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[is_available]">
		<option value="1" {if $params.is_available}selected="selected"{/if}>Available</option>
		<option value="0" {if !$params.is_available}selected="selected"{/if}>Busy</option>
	</select>
</div>

{if !empty($custom_fields)}
<b>{'common.custom_fields'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false field_wrapper="{$namePrefix}"}
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CALENDAR_EVENT field_wrapper="{$namePrefix}"}

<b>{'common.comment'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;" class="placeholders">{$params.comment}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	$action.find('textarea').elastic();
});
</script>