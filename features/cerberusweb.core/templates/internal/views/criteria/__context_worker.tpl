<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		{if empty($opers)}
		<option value="in" {if $param && $param->operator=='in'}selected="selected"{/if}>is</option>
		<option value="in or null" {if $param && $param->operator=='in or null'}selected="selected"{/if}>is blank or</option>
		<option value="not in or null" {if $param && $param->operator=='not in or null'}selected="selected"{/if}>is blank or not</option>
		<option value="not in" {if $param && $param->operator=='not in'}selected="selected"{/if}>is not</option>
		{else}
			{foreach from=$opers item=oper key=k}
				<option value="{$k}" {if $param && $param->oper==$k}selected="selected"{/if}>{$oper}</option>
			{/foreach}
		{/if}
	</select>
</blockquote>

<b>{$translate->_('common.workers')|capitalize}:</b><br>

<blockquote style="margin:5px;">
	{include file="devblocks:cerberusweb.core::internal/views/helpers/_shared_placeholder_worker_picker.tpl" param_name="worker_id" placeholders=$view->getPlaceholderLabels() show_chooser=true param=$param}
</blockquote>
