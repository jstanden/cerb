<div style="margin-left:10px;">
	<b>Enter comma-separated email addresses:</b>
	<br>
	
	<textarea rows="3" cols="60" name="{$namePrefix}[recipients]" style="width:100%;" class="placeholders email">{$params.recipients}</textarea>
</div>

<br>

{capture name=vars_addy}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
<li><label><input type="checkbox" name="{$namePrefix}[from_vars][]" value="{$var_key}" {if in_array($var_key, $params.from_vars)}checked="checked"{/if}> {$var.label}</label></li>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.vars_addy}
<div style="margin-left:10px;">
	<b>Include the email addresses from these variables:</b>
	
	<ul style="list-style:none;margin-top:5px;padding-left:10px;">
		{$smarty.capture.vars_addy nofilter}
	</ul>
</div>
{/if}

<script type="text/javascript">
$(function() {
	ajax.emailAutoComplete('fieldset#{$namePrefix} textarea.email', { multiple: true });
});
</script>