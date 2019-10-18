{capture name="addy_placeholders"}
{foreach from=$trigger->variables item=var key=var_key name=var_keys}{if $var.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}{$var.key}{if !$smarty.foreach.var_keys.last},{/if}{/if}{/foreach}
{/capture}

{capture name="server_placeholders"}
{foreach from=$trigger->variables item=var key=var_key name=var_keys}{if $var.type == "ctx_{CerberusContexts::CONTEXT_SERVER}"}{$var.key}{if !$smarty.foreach.var_keys.last},{/if}{/if}{/foreach}
{/capture}

<b>Name:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[name]" value="{$params.name}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="example.com">
</div>

<b>Server:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[server_id]" data-context="{CerberusContexts::CONTEXT_SERVER}" data-single="true" data-autocomplete="" data-autocomplete-if-empty="true" data-autocomplete-placeholders="{$smarty.capture.server_placeholders}" data-create="if-null" ><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $params.server_id}
			{if is_numeric($params.server_id)}
				{$server = DAO_Server::get($params.server_id)}
				{if $server}
					<li><input type="hidden" name="{$namePrefix}[server_id]" value="{$params.server_id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_SERVER}" data-context-id="{$server->id}">{$server->name}</a></li>
				{/if}
			{else}
				{*$var = $trigger->variables[$params.server_id]*}
				<li><input type="hidden" name="{$namePrefix}[server_id]" value="{$params.server_id}"><a href="javascript:;" class="no-underline">(variable) {$params.server_id}</a></li>
			{/if}
		{/if}
	</ul>
</div>

<b>Contacts:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[email_ids][]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-autocomplete="" data-autocomplete-placeholders="{$smarty.capture.addy_placeholders}"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{foreach from=$params.email_ids item=email_id}
		{if $email_id}
			{if is_numeric($email_id)}
				{$address = DAO_Address::get($email_id)}
				{if $address}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$address->id}{/devblocks_url}?v={$address->updated}"><input type="hidden" name="{$namePrefix}[email_ids][]" value="{$email_id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$email_id}">{$address->email}</a></li>
				{/if}
			{else}
				{*$var = $trigger->variables[$email_id]*}
				<li><input type="hidden" name="{$namePrefix}[email_ids][]" value="{$email_id}"><a href="javascript:;" class="no-underline">(variable) {$email_id}</a></li>
			{/if}
		{/if}
		{/foreach}
	</ul>
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}:</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false field_wrapper="{$namePrefix}"}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_DOMAIN field_wrapper="{$namePrefix}"}

<b>{'common.comment'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[comment]" cols="45" rows="5" style="width:100%;" class="placeholders">{$params.comment}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
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

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	// Peeks
	$action.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	// Choosers
	$action.find('button.chooser-abstract').cerbChooserTrigger();
});
</script>