{if !empty($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<div style="margin-left:10px;">
	<input type="text" name="{$namePrefix}[org]" style="width:100%;" class="placeholders input_org" value="">
	<ul class="bubbles"></ul>
</div>

<script type="text/javascript">
ajax.orgAutoComplete('fieldset#{$namePrefix} input.input_org');
</script>