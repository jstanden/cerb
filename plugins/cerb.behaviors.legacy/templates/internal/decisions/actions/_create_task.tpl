<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[title]" value="{$params.title}" class="placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'task.due_date'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[due_date]" value="{$params.due_date}" class="input_date placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="owner_id" values_to_contexts=$values_to_contexts single=true}
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.comment'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[comment]" rows="5" class="placeholders">{$params.comment}</textarea>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div></div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields field_wrapper="{$namePrefix}" custom_field_values_raw=true}
</div>
{/if}

{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TASK field_wrapper="{$namePrefix}"}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.notify_workers'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

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
{if $var.type == "ctx_{CerberusContexts::CONTEXT_TASK}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.object_vars}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Add object to list variable</label>
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.object_vars nofilter}
	</select>
</div>
{/if}
