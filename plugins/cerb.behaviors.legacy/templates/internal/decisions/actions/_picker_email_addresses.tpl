<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Enter comma-separated email addresses</label>
	<textarea rows="3" name="{$namePrefix}[recipients]" class="placeholders email">{$params.recipients}</textarea>
</div>

{capture name=vars_addy}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
<li><label><input type="checkbox" name="{$namePrefix}[from_vars][]" value="{$var_key}" {if is_array($params.from_vars) && in_array($var_key, $params.from_vars)}checked="checked"{/if}> {$var.label}</label></li>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.vars_addy}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Include the email addresses from these variables</label>
	<ul style="list-style:none;margin:0;padding-left:10px;">
		{$smarty.capture.vars_addy nofilter}
	</ul>
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	// Comma-tokenized recipient autocomplete (keep the textarea a comma-separated string). Mirrors the old
	// emailAutoComplete multiple mode. NOTE: the selector below has never matched (the action shell id is
	// {ldelim}$namePrefix{rdelim}_{ldelim}$nonce{rdelim}) — dormant since the ajax.emailAutoComplete era; the field is a
	// placeholders ScriptingEditor instead, and wiring both together is untested.
	if(window.CerbUI && CerbUI.TextChooser)
		$('fieldset#{$namePrefix} textarea.email').each(function() {
			new CerbUI.TextChooser(this, {
				minLength: 1,
				avatars: true,
				context: 'address',
				source: 'c=internal&a=invoke&module=records&action=autocomplete&context=address',
				getTerm: function(v) { const p = v.lastIndexOf(','); return (p !== -1 ? v.substring(p + 1) : v).trim(); },
				onSelect: function(item, input) {
					const val = input.value, p = val.lastIndexOf(',');
					input.value = (p !== -1 ? val.substring(0, p) + ', ' : '') + item.label + ', ';
				}
			});
		});
});
</script>
