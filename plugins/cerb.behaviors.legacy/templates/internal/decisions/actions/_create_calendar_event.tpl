<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.calendar'|devblocks_translate|capitalize}</label>
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

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[title]" value="{$params.title}" class="placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">When</label>
	<input type="text" name="{$namePrefix}[when]" value="{$params.when}" class="input_date placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Until</label>
	<input type="text" name="{$namePrefix}[until]" value="{$params.until}" class="input_date placeholders">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
	{* [TODO] Text *}
	<select name="{$namePrefix}[is_available]">
		<option value="1" {if $params.is_available}selected="selected"{/if}>{'common.available'|devblocks_translate|capitalize}</option>
		<option value="0" {if !$params.is_available}selected="selected"{/if}>{'common.busy'|devblocks_translate|capitalize}</option>
	</select>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div></div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields field_wrapper="{$namePrefix}" custom_field_values_raw=true}
</div>
{/if}

{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CALENDAR_EVENT field_wrapper="{$namePrefix}"}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.comment'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[comment]" rows="5" class="placeholders">{$params.comment}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.notify_workers'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>

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
{if $var.type == "ctx_{CerberusContexts::CONTEXT_CALENDAR_EVENT}"}
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
