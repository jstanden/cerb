<b>{'common.worker'|devblocks_translate|capitalize}:</b><br>

{$placeholders = $view->getPlaceholderLabels()}

<select name="worker_id">
{foreach from=$placeholders item=var_data key=var_key}
	{if $var_data.type == Model_CustomField::TYPE_WORKER || $var_data.context == CerberusContexts::CONTEXT_WORKER}
	<option value="{literal}{{{/literal}{$var_key}{literal}}}{/literal}" {if $param && $param->value|replace:'{':''|replace:'}':'' == $var_key}selected="selected"{/if}>- {$var_data.label} -</option>
	{/if}
{/foreach}
{foreach from=$workers item=worker key=worker_id}
	<option value="{$worker_id}" {if $param && $param->value==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
{/foreach}
</select>
<br>
<br>