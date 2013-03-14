<b>{'message.header.from'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[from_address_id]">
		<option value="0">(default)</option>
		<optgroup label="Reply-to Addresses">
			{foreach from=$replyto_addresses key=address_id item=replyto}
			{if !empty($replyto->reply_personal)}
			<option value="{$address_id}" {if $params.from_address_id==$address_id}selected="selected"{/if}>{if !empty($replyto->reply_personal)}{$replyto->reply_personal} {/if}&lt;{$replyto->email}&gt;</option>
			{else}
			<option value="{$address_id}" {if $params.from_address_id==$address_id}selected="selected"{/if}>{$replyto->email}</option>
			{/if}
			{/foreach}
		</optgroup>
		{if !empty($placeholders)}
		<optgroup label="Placeholders">
		{foreach from=$placeholders item=label key=placeholder}
		<option value="{$placeholder}" {if $params.from_address_id==$placeholder}selected="selected"{/if}>{$label}</option>
		{/foreach}
		</optgroup>
		{/if}
	</select>
</div>

<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[to]" value="{$params.to}" size="45" style="width:100%;" class="placeholders">
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[to_var][]" value="{$var_key}" {if in_array($var_key, $params.to_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders">
</div>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<b>{'message.headers.custom'|devblocks_translate|capitalize}:</b> (one per line, e.g. "X-Precedence: Bulk")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[headers]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.headers}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>