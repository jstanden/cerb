<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.to'|devblocks_translate|capitalize}</label>
	<select name="{$namePrefix}[group_id]">
		{foreach from=$groups item=group key=group_id}
		<option value="{$group_id}" {if $group_id==$params.group_id}selected="selected"{/if}>{$group->name}</option>
		{/foreach}
	</select>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.participants'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[requesters]" value="{$params.requesters}" class="placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" class="placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.content'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[content]" rows="3" class="placeholders">{$params.content}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[status_id]" value="{Model_Ticket::STATUS_OPEN}" {if empty($params.status_id)}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[status_id]" value="{Model_Ticket::STATUS_WAITING}" {if Model_Ticket::STATUS_WAITING==$params.status_id}checked="checked"{/if}> {'status.waiting'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[status_id]" value="{Model_Ticket::STATUS_CLOSED}" {if Model_Ticket::STATUS_CLOSED==$params.status_id}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="peek-status-reopen" style="{if empty($params.status_id)}display:none;{/if}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'common.reopen_at'|devblocks_translate|capitalize}</label>
		<input type="text" name="{$namePrefix}[reopen_at]" value="{$params.reopen_at}" class="placeholders">
	</div>
</div>

{* Check for attachment variables *}
{capture name="attachment_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ATTACHMENT}"}
<label><input type="checkbox" name="{$namePrefix}[attachment_vars][]" value="{$var_key}" {if is_array($params.attachment_vars) && in_array($var_key, $params.attachment_vars)}checked="checked"{/if}> {$var.label}</label>
{/if}
{/foreach}{/capture}

{if $smarty.capture.attachment_vars}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Attach the files from these variables</label>
	<div>
		{$smarty.capture.attachment_vars nofilter}
	</div>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="owner_id" values_to_contexts=$values_to_contexts single=true}
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields field_wrapper="{$namePrefix}" custom_field_values_raw=true}
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>

{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Link to</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}

{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET field_wrapper="{$namePrefix}"}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also create records in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

{* Check for object variables *}
{capture name="object_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_TICKET}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}{/capture}

{if $smarty.capture.object_vars}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Add object to list variable</label>
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.object_vars nofilter}
	</select>
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	$action.find('input:radio[name$="[status_id]"]').change(function() {
		var $val = $(this).val();

		if($val != '0')
			$action.find('div.peek-status-reopen').fadeIn();
		else
			$action.find('div.peek-status-reopen').hide();
	});
});
</script>
