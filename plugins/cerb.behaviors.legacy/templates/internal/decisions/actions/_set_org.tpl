{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">On</label>
	<select name="{$namePrefix}[on]">
		{foreach from=$values_to_contexts item=context_data key=val_key}
		{if $context_data.label}<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>{/if}
		{/foreach}
	</select>
</div>
{/if}

<div class="cerb-ui-form--field">
	<input type="text" name="{$namePrefix}[org]" class="placeholders input_org" value="{$params.org}">
	<ul class="bubbles"></ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
// NOTE: the selector below has never matched (the action shell id is {ldelim}$namePrefix{rdelim}_{ldelim}$nonce{rdelim}) —
// dormant since the ajax era; the field is a placeholders ScriptingEditor instead, and wiring both together is untested.
if(window.CerbUI && CerbUI.TextChooser)
	$('fieldset#{$namePrefix} input.input_org').each(function() {
		new CerbUI.TextChooser(this, {
			icon: 'building-office',
			avatars: true,
			context: 'org',
			source: 'c=internal&a=invoke&module=records&action=autocomplete&context=org',
			onSelect: function(item, input) { input.value = item.label; } // post the org name, not its id
		});
	});
</script>
