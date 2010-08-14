{$view_filters = $view->getFields()}
{if !empty($params)}
{foreach from=$params item=param key=param_key name=params}
	{if !$nested}<label><input type="checkbox" name="field_deletes[]" value="{$param_key|escape}"> {/if}
		
	{if '*_' == substr($param_key,0,2)}
		{$view->renderVirtualCriteria($param)}
	{elseif is_array($param)}
		{foreach from=$param item=p name=p}
			{if $smarty.foreach.p.first}
			{else}
				{if is_array($p)}
					{include file="file:$core_tpl/internal/views/criteria_list_params.tpl" params=$p nested=true}
				{else}
					{assign var=field value=$p->field} 
					{$view_filters.$field->db_label|capitalize} 
					{$p->operator}
					<b>{$view->renderCriteriaParam($p)}</b>
				{/if}
				
				{if !$smarty.foreach.p.last} <i>{$param.0}</i> {/if}
			{/if}
		{/foreach}
	{else}
		{assign var=field value=$param->field} 
		{$view_filters.$field->db_label|capitalize} 
		{$param->operator}
		<b>{$view->renderCriteriaParam($param)}</b>
		
		{if $nested}{if $smarty.foreach.params.first}({/if}
			{if !$smarty.foreach.params.first && !$smarty.foreach.params.last}<i>{$params.0}</i>{/if}
			{if $smarty.foreach.params.last}){/if} 
		{/if}
	{/if}
		
	{if !$nested}</label><br>{/if}
{/foreach}
{/if}
