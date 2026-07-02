<b>Name:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[name]" value="{$params.name}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="example.com">
</div>

<b>Server:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<a class="cerb-chooser cerb-server-chooser" data-context="{CerberusContexts::CONTEXT_SERVER}" data-single="true">ID</a>:
	<input type="text" name="{$namePrefix}[server_id]" value="{$params.server_id}" class="placeholders" size="40" autocomplete="off" spellcheck="false">
</div>

<b>Contacts:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<div class="cerb-ui-record-chooser cerb-contacts-chooser">
		{foreach from=$params.email_ids item=email_id}
			{if $email_id && is_numeric($email_id) && isset($contact_addresses[$email_id])}
				{$address = $contact_addresses[$email_id]}
				<li data-context-id="{$email_id}" data-label="{$address->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$address->id}{/devblocks_url}?v={$address->updated}"></li>
			{/if}
		{/foreach}
	</div>
	{* Preserve any saved trigger-variable values (non-numeric); the picker is records-only but shouldn't drop them on save *}
	{foreach from=$params.email_ids item=email_id}
		{if $email_id && !is_numeric($email_id)}
		<input type="hidden" name="{$namePrefix}[email_ids][]" value="{$email_id}">
		{/if}
	{/foreach}
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}:</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false field_wrapper="{$namePrefix}"}
</fieldset>
{/if}

{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_DOMAIN field_wrapper="{$namePrefix}"}

<b>{'common.comment'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;" class="placeholders">{$params.comment}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}: (deprecated)</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

<b>Also create records in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

{* Check for object variables *}
{capture name="object_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_DOMAIN}"}
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	// Peeks
	$action.find('.cerb-peek-trigger').cerbPeekTrigger();

	if(window.CerbUI && CerbUI.RecordChooser) {
		// Server: placeholder-capable (type a variable, or pick a server via the ID link)
		$action.find('.cerb-server-chooser').each(function() {
			CerbUI.RecordChooser.pickerLink(this, { input: $action.find('input[name="{$namePrefix}[server_id]"]')[0] });
		});

		// Contacts: pick Address records
		$action.find('.cerb-contacts-chooser').each(function() {
			new CerbUI.RecordChooser(this, { context: '{CerberusContexts::CONTEXT_ADDRESS}', name: '{$namePrefix}[email_ids]', multiple: true, emptyIcon: 'mail' });
		});
	}
});
</script>