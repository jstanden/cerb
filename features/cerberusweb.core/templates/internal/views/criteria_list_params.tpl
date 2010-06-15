{$view_filters = $view->getParamsAvailable()}
{if !empty($params)}
{foreach from=$params item=param key=param_key name=params}
	{if !$nested}
	<tr>
		<td width="100%">
		<span class="cerb-sprite sprite-data_find"></span>
	{/if}
		
	{if is_array($param)}
		{foreach from=$param item=p name=p}
			{if $smarty.foreach.p.first}
			{else}
				{if is_array($p)}
					{include file="file:$core_tpl/internal/views/criteria_list_params.tpl" params=$p nested=true}
				{else}
					{assign var=field value=$p->field} 
					{$view_filters.$field->db_label} 
					{$p->operator}
					<b>{$view->renderCriteriaParam($p)}</b>
				{/if}
				
				{if !$smarty.foreach.p.last} <i>{$param.0}</i> {/if}
			{/if}
		{/foreach}
	{else}
		{assign var=field value=$param->field} 
		{$view_filters.$field->db_label} 
		{$param->operator}
		<b>{$view->renderCriteriaParam($param)}</b>
		
		{if $nested}{if $smarty.foreach.params.first}({/if}
			{if !$smarty.foreach.params.first && !$smarty.foreach.params.last}<i>{$params.0}</i>{/if}
			{if $smarty.foreach.params.last}){/if} 
		{/if}
	{/if}
		
	{if !$nested}
		</td>
		<td width="0%" nowrap="nowrap" valign="top" align="center">
			{if !$batchDelete}
			<a href="javascript:;" onclick="document.{$view->id}_criteriaForm.field.value='{$param_key}';document.{$view->id}_criteriaForm.submit();"><span class="cerb-sprite sprite-forbidden"></span></a>
			{else}
			<input type="checkbox" name="field_deletes[]" value="{$param->field}">
			{/if}
		</td>
	</tr>
	{/if}
{/foreach}
{/if}
