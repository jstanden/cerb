{if !empty($params)}
{foreach from=$params item=param key=param_key name=params}
	{if !$nested}
	<tr>
		<td width="100%">
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="top">
	{/if}
		
	{if is_array($param)}
		{foreach from=$param item=p name=p}
			{if $smarty.foreach.p.first}
			{else}
				{if is_array($p)}
					{include file="file:$core_tpl/internal/views/criteria_list_params.tpl" params=$p nested=true}
				{else}
					{assign var=field value=$p->field} 
					{$view_fields.$field->db_label} 
					{$p->operator}
					<b>{$view->renderCriteriaParam($p)}</b>
				{/if}
				
				{if !$smarty.foreach.p.last} <i>{$param.0}</i> {/if}
			{/if}
		{/foreach}
	{else}
		{assign var=field value=$param->field} 
		{$view_fields.$field->db_label} 
		{$param->operator}
		<b>{$view->renderCriteriaParam($param)}</b>
		
		{if $nested}{if $smarty.foreach.params.first}({/if}
			{if !$smarty.foreach.params.first && !$smarty.foreach.params.last}<i>{$params.0}</i>{/if}
			{if $smarty.foreach.params.last}){/if} 
		{/if}
	{/if}
		
	{if !$nested}
		</td>
		<td width="0%" nowrap="nowrap" valign="top"><a href="javascript:;" onclick="document.{$view->id}_criteriaForm.field.value='{$param_key}';document.{$view->id}_criteriaForm.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_error.gif{/devblocks_url}" border="0" align="top"></a></td>
	</tr>
	{/if}
{/foreach}
{/if}
