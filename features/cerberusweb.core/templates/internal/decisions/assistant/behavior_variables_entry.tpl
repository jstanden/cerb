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
			<textarea name="{$field_name}[{$var.key}]" style="height:50px;width:98%;" {if $with_placeholders}class="placeholders"{/if}>{$variable_values.$var_key}</textarea>
			{else}
			<input type="text" name="{$field_name}[{$var.key}]" value="{$variable_values.$var_key}" style="width:98%;" {if $with_placeholders}class="placeholders"{/if}>
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
		<label><input type="radio" name="{$field_name}[{$var.key}]" value="1" {if $variable_values.$var_key}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label> 
		<label><input type="radio" name="{$field_name}[{$var.key}]" value="0" {if !$variable_values.$var_key}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label> 
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
			<button type="button" class="chooser" context="{$context}" node_name="{$field_name}[{$var.key}]"><span class="cerb-sprite sprite-view"></span></button>
			<ul class="bubbles chooser-container" style="display:inline-block;">
				{if is_array($variable_values.$var_key)}
				{foreach from=$variable_values.$var_key item=context_id}
					{CerberusContexts::getContext($context, $context_id, $null, $var_values, true)}
					<li>{$var_values._label}<input type="hidden" name="{$field_name}[{$var.key}][]" title="{$var_values._label}" value="{$context_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
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
// Choosers
$('#{$vars_uniqid} button.chooser').each(function() {
	var $this = $(this);
	ajax.chooser(this, $this.attr('context'), $this.attr('node_name'), { autocomplete:false });
});
</script>