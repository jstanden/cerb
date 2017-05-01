{$vars_uniqid = uniqid()}
{if !isset($field_name)}{$field_name = "var_vals"}{/if}
<div id="{$vars_uniqid}">
{foreach from=$variables key=var_key item=var}
{if !$var.is_private}
<div>
	<input type="hidden" name="var_keys[]" value="{$var.key}">
	<b>{$var.label}:</b>
	<div style="margin:0px 0px 5px 15px;">
		{if $var.type == 'S'}
			{if $var.params.widget=='multiple'}
			<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" class="{if $with_placeholders}placeholders {/if}">{$variable_values.$var_key}</textarea>
			{else}
			<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" class="{if $with_placeholders}placeholders {/if}{if $var.params.mentions}cerb-mentions {/if}">
			{/if}
		{elseif $var.type == 'D'}
		<select name="{$field_name}[{$var.key}]">
			{$options = DevblocksPlatform::parseCrlfString($var.params.options, true)}
			{if is_array($options)}
			{foreach from=$options item=option}
			<option value="{$option}" {if $variable_values.$var_key==$option}selected="selected"{/if}>{$option}</option>
			{/foreach}
			{/if}
		</select>
		{elseif $var.type == 'N'}
		<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" {if $with_placeholders}class="placeholders"{/if}>
		{elseif $var.type == 'C'}
		<label><input type="radio" name="{$field_name}[{$var.key}]" value="1" {if (!is_null($variable_values.$var_key) && $variable_values.$var_key) || (is_null($variable_values.$var_key) && $var.params.checkbox_default_on)}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label> 
		<label><input type="radio" name="{$field_name}[{$var.key}]" value="0" {if (!is_null($variable_values.$var_key) && !$variable_values.$var_key) || (is_null($variable_values.$var_key) && empty($var.params.checkbox_default_on))}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label> 
		{elseif $var.type == 'E'}
		<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" {if $with_placeholders}class="placeholders"{/if}>
		{elseif $var.type == 'W'}
		{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
		<select name="{$field_name}[{$var.key}]">
			<option value=""></option>
			{foreach from=$workers item=worker}
			<option value="{$worker->id}" {if $variable_values.$var_key==$worker->id}selected="selected"{/if}>{$worker->getName()}</option>
			{/foreach}
		</select>
		{elseif substr($var.type,0,4) == 'ctx_'}
			{$context = substr($var.type,4)}
			<button type="button" class="cerb-chooser-trigger" data-context="{$context}" data-field-name="{$field_name}[{$var.key}][]"><span class="glyphicons glyphicons-search"></span></button>
			<ul class="bubbles chooser-container" style="display:inline-block;">
				{if is_array($variable_values.$var_key)}
				{foreach from=$variable_values.$var_key item=context_id}
					{CerberusContexts::getContext($context, $context_id, $null, $var_values, true)}
					<li>{$var_values._label}<input type="hidden" name="{$field_name}[{$var.key}][]" title="{$var_values._label}" value="{$context_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
				{/foreach}
				{/if}
			</ul>
		{/if}
	</div>
</div>
{/if}
{/foreach}
</div>

<script type="text/javascript">
$(function() {
	var $container = $('#{$vars_uniqid}');
	
	// Elastic textareas
	$container.find('textarea').autosize();
	
	// Choosers
	$container.find('button.cerb-chooser-trigger')
		.cerbChooserTrigger()
		;
});
</script>