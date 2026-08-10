{$fieldset_domid = "bulk_automation_fieldset_"|cat:uniqid()}
<fieldset class="block bulk-automation-fieldset" id="{$fieldset_domid}" data-idx="{$idx}" data-key="{$handler_key}">
	<legend>
		<label>
			<input type="hidden" name="automations[{$idx}][handler]" value="{$handler_key}">
			<span class="cerb-icons cerb-icon-bot"></span> {$automation->name}
		</label>
	</legend>
	<span class="cerb-icons cerb-icon-circle-remove delete" style="font-size:16px;cursor:pointer;float:right;margin-top:-20px;display:none;"></span>

	{if $automation->description}
		<p><em>{$automation->description}</em></p>
	{/if}

	{if empty($inputs)}
		<p></p>
	{else}
		<table cellspacing="0" cellpadding="2" width="100%">
		{foreach from=$inputs key=k item=input}
			{$field_name = "automations[$idx][inputs][{$input.key}]"}
			{$value = $input.default|default:''}
			<tr>
				<td width="0%" nowrap="nowrap" align="right" valign="top" style="padding-right:5px;">
					<label>
						<input type="checkbox" name="" value="" {if $input.required}checked disabled{/if}>
						{$input.label|default:$input.key|capitalize}:
					</label>
				</td>
				<td width="100%" valign="top">
					{if $input.type == 'text' && !empty($input.allowed_values)}
						<select name="{$field_name}">
							{foreach from=$input.allowed_values key=opt_value item=opt_label}
								<option value="{$opt_value}" {if $value == $opt_value}selected{/if}>{$opt_label}</option>
							{/foreach}
						</select>
					{elseif $input.type == 'text' && $input.multiline}
						<textarea name="{$field_name}" rows="3" style="width:95%;" placeholder="{$input.placeholder|default:''}">{$value}</textarea>
					{elseif $input.type == 'text'}
						<input type="text" name="{$field_name}" size="48" value="{$value}" placeholder="{$input.placeholder|default:''}">
					{elseif $input.type == 'number'}
						<input type="number" name="{$field_name}" value="{$value}" placeholder="{$input.placeholder|default:''}" class="input_number">
					{elseif $input.type == 'bool'}
						<label>
							<input type="hidden" name="{$field_name}" value="0">
							<input type="checkbox" name="{$field_name}" value="1" {if $value}checked{/if}>
							{$input.checkbox_label|default:''}
						</label>
					{elseif $input.type == 'dropdown'}
						<select name="{$field_name}">
							{foreach from=$input.options key=opt_value item=opt_label}
								<option value="{$opt_value}" {if $value == $opt_value}selected{/if}>{$opt_label}</option>
							{/foreach}
						</select>
					{else}
						<input type="text" name="{$field_name}" size="48" value="{$value}" placeholder="{$input.placeholder|default:''}">
					{/if}

					{if $input.description}
						<br><span style="color:var(--cerb-color-background-contrast-125); font-size:90%;">{$input.description}</span>
					{/if}
				</td>
			</tr>
		{/foreach}
		</table>
	{/if}
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$('#{$fieldset_domid}')
	.hover(
		function() { $(this).find('> span.delete').show(); },
		function() { $(this).find('> span.delete').hide(); }
	)
	.find('> span.delete')
	.click(function() {
		const $fieldset = $(this).closest('fieldset');
		const key = $fieldset.attr('data-key') || '';

		$fieldset.fadeTo('fast', 0.0, function() {
			const event = $.Event('bulk_automation_remove');
			event.key = key;
			$(this).trigger(event);
			$(this).remove();
		});
	});
</script>
