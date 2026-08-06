{$vars_uniqid = uniqid()}
{if !isset($field_name)}{$field_name = "var_vals"}{/if}
<div id="{$vars_uniqid}">
{foreach from=$variables key=var_key item=var}
{if !$var.is_private}
<div>
	<input type="hidden" name="var_keys[]" value="{$var.key}">
	<b>{$var.label}:</b>
	<div style="margin:0px 0px 5px 15px;">
		{if $var.type == Model_CustomField::TYPE_SINGLE_LINE}
			{if $var.params.widget=='multiple'}
			<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" class="{if $with_placeholders}placeholders {/if}">{$variable_values.$var_key}</textarea>
			{else}
			<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" class="{if $with_placeholders}placeholders {/if}{if $var.params.mentions}cerb-mentions {/if}">
			{/if}
		{elseif $var.type == Model_CustomField::TYPE_DROPDOWN}
			{$options = DevblocksPlatform::parseCrlfString($var.params.options, true)}
			{if $with_placeholders}
				<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" class="{if $with_placeholders}placeholders {/if}">{$variable_values.$var_key}</textarea>
				{if is_array($options)}
				<div>
					<small><b>Options:</b> {implode(', ', $options)}</small>
				</div>
				{/if}
			{else}
				<select name="{$field_name}[{$var.key}]">
				{foreach from=$options item=option}
				<option value="{$option}" {if $variable_values.$var_key==$option}selected="selected"{/if}>{$option}</option>
				{/foreach}
				</select>
			{/if}
		{elseif $var.type == Model_CustomField::TYPE_NUMBER}
		<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" {if $with_placeholders}class="placeholders"{/if}>
		{elseif $var.type == Model_CustomField::TYPE_LINK}
			<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" {if $with_placeholders}class="placeholders"{/if}>
		{elseif $var.type == Model_CustomField::TYPE_CHECKBOX}
			{if $with_placeholders}
				<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" class="{if $with_placeholders}placeholders {/if}">{$variable_values.$var_key}</textarea>
				<div>
					<small><b>Options:</b> 0, 1</small>
				</div>
			{else}
				<label><input type="radio" name="{$field_name}[{$var.key}]" value="1" {if (!is_null($variable_values.$var_key) && $variable_values.$var_key) || (is_null($variable_values.$var_key) && $var.params.checkbox_default_on)}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label> 
				<label><input type="radio" name="{$field_name}[{$var.key}]" value="0" {if (!is_null($variable_values.$var_key) && !$variable_values.$var_key) || (is_null($variable_values.$var_key) && empty($var.params.checkbox_default_on))}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label> 
			{/if}
		{elseif $var.type == Model_CustomField::TYPE_DATE}
		<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" {if $with_placeholders}class="placeholders"{/if}>
		{elseif $var.type == Model_CustomField::TYPE_WORKER}
			{if $with_placeholders}
			<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" class="{if $with_placeholders}placeholders {/if}">{$variable_values.$var_key}</textarea>
			<div>
				<small>Enter a <a class="cerb-worker-chooser-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-single="true">worker ID</a></small>
			</div>
			{else}
			{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
			<select name="{$field_name}[{$var.key}]">
				{foreach from=$workers item=worker}
				<option value="{$worker->id}" {if $variable_values.{$var.key}==$worker->id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			{/if}
		{elseif substr($var.type,0,4) == 'ctx_'}
			{$context = substr($var.type,4)}
			<div class="cerb-ui-record-chooser cerb-record-chooser-ctx" data-context="{$context}" data-name="{$field_name}[{$var.key}]">
				{if is_array($variable_values.$var_key)}
				{foreach from=$variable_values.$var_key item=context_id}
					{$null = []}
					{$var_values = []}
					{CerberusContexts::getContext($context, $context_id, $null, $var_values, true)}
					<li data-context="{$context}" data-context-id="{$context_id}" data-label="{$var_values._label}"></li>
				{/foreach}
				{/if}
			</div>
		{/if}
	</div>
</div>
{/if}
{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $container = $('#{$vars_uniqid}');

	if(window.CerbUI && CerbUI.RecordChooser) {
		// Record-list variables (ctx_*): a multi chooser; context + posted field name are carried per element
		$container.find('.cerb-record-chooser-ctx').each(function() {
			new CerbUI.RecordChooser(this, {
				context: this.getAttribute('data-context'),
				name: this.getAttribute('data-name'),
				multiple: true
			});
		});

		// Worker variable (with placeholders): the "worker ID" link inserts a picked worker's id into the textarea
		$container.find('a.cerb-worker-chooser-trigger').each(function() {
			CerbUI.RecordChooser.pickerLink(this, {
				query: this.getAttribute('data-query') || '',
				input: this.closest('div').previousElementSibling
			});
		});
	}
});
</script>