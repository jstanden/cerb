{$view_filters = $view->getFields()}
{if $readonly}<ul class="bubbles">{/if}
{if !empty($params)}
{foreach from=$params item=param key=param_key name=params}
	{if !$nested && !$readonly}<label><input type="checkbox" name="field_deletes[]" value="{$param_key}"> {/if}
	{if !$nested && $readonly}<li>{/if}
		
	{if '*_' == substr($param_key,0,2)}
		{$view->renderVirtualCriteria($param)}
	{elseif is_array($param)}
		{foreach from=$param item=p name=p}
			{if $smarty.foreach.p.first}
			{else}
				{if is_array($p)}
					{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$p nested=true}
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
		{* [TODO] Add operator labels to platform *}
		{if $param->operator=='='}
			is
		{elseif $param->operator=='!='}
			is not 
		{elseif $param->operator=='in'}
			is    
		{elseif $param->operator=='in or null'}
			is blank{if !empty($param->value)} or{/if} 
		{elseif $param->operator=='not in'}
			is not
		{elseif $param->operator=='not in or null'}
			is blank{if !empty($param->value)} or not{/if}  
		{elseif $param->operator=='is null'}
			is {if empty($param->value)}blank{/if}
		{elseif $param->operator=='is not null'}
			is not {if empty($param->value)}blank{/if}
		{else} 
			{$param->operator}
		{/if}
		<b>{$view->renderCriteriaParam($param)}</b>
		
		{if $nested}{if $smarty.foreach.params.first}({/if}
			{if !$smarty.foreach.params.first && !$smarty.foreach.params.last}<i>{$params.0}</i>{/if}
			{if $smarty.foreach.params.last}){/if} 
		{/if}
	{/if}
		
	{if !$nested && !$readonly}</label><br>{/if}
	{if !$nested && $readonly}<a href="javascript:;" class="delete" onclick="ajax.viewRemoveFilter('{$view->id}', ['{$param_key}']);" style="margin-left:-18px;display:none;"><span class="cerb-sprite2 sprite-cross-circle"></span></a></li>{/if}
{/foreach}
{if $readonly}</ul>{/if}
{else}{*empty*}
	{if !$nested && $readonly}<li><i>{'common.none'|devblocks_translate|lower}</i></li>{/if}
{/if}
