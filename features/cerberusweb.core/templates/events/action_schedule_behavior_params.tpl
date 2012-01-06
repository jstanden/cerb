{if !empty($macro_params)}
<div class="block" style="margin-left:10px;margin-bottom:0.5em;">
	{foreach from=$macro_params item=var key=var_key}
		<div>
			<b>{$var.label}:</b><br>
			{if $var.type == 'S'}
			<input type="text" name="{$namePrefix}[{$var_key}]" value="{$params.$var_key}" style="width:98%;" class="placeholders">
			{elseif $var.type == 'N'}
			<input type="text" name="{$namePrefix}[{$var_key}]" value="{$params.$var_key}" class="placeholders">
			{elseif $var.type == 'C'}
			<label><input type="radio" name="{$namePrefix}[{$var_key}]" value="1" {if $params.$var_key}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label> 
			<label><input type="radio" name="{$namePrefix}[{$var_key}]" value="0" {if !$params.$var_key}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label> 
			{elseif $var.type == 'E'}
			<input type="text" name="{$namePrefix}[{$var_key}]" value="{$params.$var_key}" style="width:98%;" class="placeholders">
			{elseif $var.type == 'W'}
			{if !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
			<select name="{$namePrefix}[{$var_key}]">
				<option value=""></option>
				{foreach from=$workers item=worker}
				<option value="{$worker->id}" {if $params.$var_key==$worker->id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
			{/if}
		</div>
	{/foreach}
</div>
{/if}