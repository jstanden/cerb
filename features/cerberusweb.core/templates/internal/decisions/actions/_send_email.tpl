<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div>
<input type="text" name="{$namePrefix}[to]" value="{$params.to}" size="45" style="width:100%;" class="placeholders">
</div>
<ul class="bubbles">
{foreach from=$trigger->variables item=var_data key=var_key}
	{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
		<li><label><input type="checkbox" name="{$namePrefix}[to_var][]" value="{$var_key}" {if in_array($var_key, $params.to_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
	{/if}
{/foreach}
</ul>
<br>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders">
</div>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>