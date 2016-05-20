{$view_filters = $view->getFields()}
{if $readonly}<ul class="bubbles">{/if}
{if !empty($params)}
{foreach from=$params item=param key=param_key name=params}
	{if !$nested && !$readonly}<label><input type="checkbox" name="field_deletes[]" value="{$param_key}"> {/if}
	{if !$nested && $readonly}<li class="bubble-blue" style="position:relative;{if is_array($param)}white-space:normal;{/if}">{/if}
		
	{if '*_' == substr($param->field,0,2)}
		{$view->renderVirtualCriteria($param)}
	{elseif is_array($param)}
		{foreach from=$param item=p name=p}
			{if $smarty.foreach.p.first}
			{else}
				{if is_array($p)}
					{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$p nested=true}
				{else}
					{if '*_' == substr($p->field,0,2)}
						{$view->renderVirtualCriteria($p)}
					{else}
						{$view_filters.{$p->field}->db_label|capitalize} 
						{if $p->operator=='='}
							is
						{elseif $p->operator=='equals or null'}
							is  
						{elseif $p->operator=='!='}
							is not 
						{elseif $p->operator=='like'}
							is
						{elseif $p->operator=='not like'}
							is not 
						{elseif $p->operator=='in'}
							is    
						{elseif $p->operator=='in or null'}
							is blank{if !empty($p->value)} or{/if} 
						{elseif $p->operator=='not in'}
							is not
						{elseif $p->operator=='not in or null'}
							is blank{if !empty($p->value)} or not{/if} 
						{elseif $p->operator=='is null'}
							is {if empty($p->value)}blank{/if}
						{elseif $p->operator=='is not null'}
							is not {if empty($p->value)}blank{/if}
						{elseif $param->operator=='between'}
							is between
						{elseif $param->operator=='not between'}
							is not between
						{elseif $p->operator=='fulltext'}
							search
						{else} 
							{$p->operator}
						{/if}
						<b>{$view->renderCriteriaParam($p)}</b>
					{/if}
				{/if}
				
				{if !$smarty.foreach.p.last} <tt style="color:black;font-weight:bold;padding:0px 5px;">{$param.0}</tt> {/if}
			{/if}
		{/foreach}
	{else}
		{$field = $param->field} 
		{$view_filters.$field->db_label|capitalize}
		{* [TODO] Add operator labels to platform *}
		{if $param->operator=='='}
			is
		{elseif $param->operator=='equals or null'}
			is  
		{elseif $param->operator=='!='}
			is not 
		{elseif $param->operator=='like'}
			is
		{elseif $param->operator=='not like'}
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
			is {if empty($param->value)}null{/if}
		{elseif $param->operator=='is not null'}
			is {if empty($param->value)}not null{/if}
		{elseif $param->operator=='between'}
			is between
		{elseif $param->operator=='not between'}
			is not between
		{elseif $param->operator=='fulltext'}
			search
		{else} 
			{$param->operator}
		{/if}
		<b>{$view->renderCriteriaParam($param)}</b>
		
		{if $nested}{if $smarty.foreach.params.first}<tt style="color:black;font-weight:bold;padding:0px 2px 0px 0px;">(</tt>{/if}
			{if !$smarty.foreach.params.first && !$smarty.foreach.params.last}<tt style="color:black;font-weight:bold;padding:0px 5px;">{$params.0}</tt>{/if}
			{if $smarty.foreach.params.last}<tt style="color:black;font-weight:bold;padding:0px 0px 0px 2px;">)</tt>{/if} 
		{/if}
	{/if}
		
	{if !$nested && !$readonly}</label><br>{/if}
	{if !$nested && $readonly}<a href="javascript:;" class="delete" onclick="ajax.viewRemoveFilter('{$view->id}', ['{$param_key}']);" style="position:absolute;top:-7px;right:-6px;display:none;"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span></a></li>{/if}
{/foreach}
{if $readonly}</ul>{/if}
{else}{*empty*}
	{if !$nested && $readonly}<li><i>{'common.none'|devblocks_translate|lower}</i></li>{/if}
{/if}
