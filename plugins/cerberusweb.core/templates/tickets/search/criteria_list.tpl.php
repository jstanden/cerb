{if !empty($params)}
<div class="block">
<table cellpadding="2" cellspacing="0" width="200" border="0">
	<tr>
		<td nowrap="nowrap">
			<h2 style="display:inline;">Current Criteria</h2> 
			[ <a href="{devblocks_url}c=tickets&a=resetCriteria{/devblocks_url}">reset</a> ]
		</td>
	</tr>
	<!-- 
	<tr>
		<td>
			{if $view->type=='S'}
				Search: <b>{$view->name}</b><br>
			{/if}
			|
			<a href="javascript:;" onclick="ajax.getSaveSearch('{$divName}');">save as</a> |
			<a href="javascript:;" onclick="ajax.getLoadSearch('{$divName}');">load</a>
			{if $view->type=='S'} | <a href="javascript:;" onclick="ajax.deleteSearch('{$view->id}');">delete</a>{/if}
			<br>
			<form id="{$divName}_control"></form>
		</td>
	</tr>
 -->
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0">
				{if !empty($params)}
				{foreach from=$params item=param}
					<tr>
						<td width="100%">
						{if $param->field=='t_mask'}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.mask')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>
						{elseif $param->field=="t_is_closed"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.status')|capitalize} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{if 0== $p}{$translate->_('status.open')|capitalize}{else}{$translate->_('status.closed')|capitalize}{/if}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=='t_spam_score'}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.spam_score')|capitalize} 
							{$param->operator} 
							<b>{math equation="x*100" x=$param->value}</b>%
						{elseif $param->field=="tm_id"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('common.group')|capitalize}
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{$teams.$p->name}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=="t_category_id"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('common.bucket')|capitalize}
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{if 0==$p}Inbox{else}{$buckets.$p->name}{/if}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=="t_subject"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.subject')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>
						{elseif $param->field=="t_first_wrote"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.first_wrote')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>
						{elseif $param->field=="t_last_wrote"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.last_wrote')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>
						{elseif $param->field=="t_created_date"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.created')|capitalize} 
							{$param->operator}
							{if is_array($param->value)}
								<b>{$param->value[0]}</b> and <b>{$param->value[1]}</b>
							{else}
								<b>$param->value</b>
							{/if}
						{elseif $param->field=="t_updated_date"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.updated')|capitalize} 
							{$param->operator} 
							{if is_array($param->value)}
								<b>{$param->value[0]}</b> and <b>{$param->value[1]}</b>
							{else}
								<b>$param->value</b>
							{/if}
						{elseif $param->field=="t_last_action_code"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.last_action')|capitalize} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{if 'O'== $p}{'New Ticket'|capitalize}{elseif 'R'==$p}{'Customer Reply'|capitalize}{elseif 'W'==$p}{'Worker Reply'|capitalize}{/if}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=="t_last_worker_id"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.last_worker')|capitalize} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							{if empty($p)}
								<b>Nobody</b>
							{elseif isset($workers.$p)}
								<b>{$workers.$p->getName()|capitalize}</b>
							 {/if}
							{if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=="t_next_worker_id"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.next_worker')|capitalize} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							{if empty($p)}
								<b>Nobody</b>
							{elseif isset($workers.$p)}
								<b>{$workers.$p->getName()|capitalize}</b>
							 {/if}
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						{elseif $param->field=="t_next_action"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('ticket.next_action')|capitalize} 
							{$param->operator}
							<b>{$param->value}</b>
						{*
						{elseif $param->field=="ra_email"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('requester')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>*}
						{elseif $param->field=="mc_content"}
							<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
							{$translate->_('message.content')|capitalize} 
							{$param->operator} 
							<b>{$param->value}</b>
						{else}
						{/if}
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="{devblocks_url}c=tickets&a=removeCriteria&field={$param->field}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_error.gif{/devblocks_url}" border="0" align="absmiddle"></a></td>
					</tr>
				{/foreach}
				{/if}
			</table>
		</td>
	</tr>
</table>
</div>
<br>
{/if}

<div class="block">
	<form action="{devblocks_url}{/devblocks_url}" method="POST">
	<input type="hidden" name="c" value="tickets">
	<input type="hidden" name="a" value="addCriteria">
	
	<h2>Add Criteria</h2>
	<b>Field:</b><br>
	<blockquote style="margin:5px;">
		<select name="field" onchange="genericAjaxGet('addCriteriaOptions','c=tickets&a=getCriteria&field='+selectValue(this));">
			<option value="">-- choose --</option>
			<option value="t_subject">{$translate->_('ticket.subject')|capitalize}</option>
			<option value="t_is_closed">{$translate->_('ticket.status')|capitalize}</option>
			<option value="tm_id">Group/Bucket</option>
			<option value="t_first_wrote">{$translate->_('ticket.first_wrote')|capitalize}</option>
			<option value="t_last_wrote">{$translate->_('ticket.last_wrote')|capitalize}</option>
			<option value="t_spam_score">{$translate->_('ticket.spam_score')|capitalize}</option>
			<option value="t_created_date">{$translate->_('ticket.created')|capitalize}</option>
			<option value="t_updated_date">{$translate->_('ticket.updated')|capitalize}</option>
			<option value="t_mask">{$translate->_('ticket.mask')|capitalize}</option>
			<!-- <option value="ra_email">{$translate->_('requester')|capitalize}</option> -->
			<option value="mc_content">{$translate->_('message.content')|capitalize}</option>
			<option value="t_last_action_code">{$translate->_('ticket.last_action')|capitalize}</option>
			<option value="t_last_worker_id">{$translate->_('ticket.last_worker')|capitalize}</option>
			<option value="t_next_action">{$translate->_('ticket.next_action')|capitalize}</option>
			<option value="t_next_worker_id">{$translate->_('ticket.next_worker')|capitalize}</option>
		</select>
	</blockquote>

	<div id="addCriteriaOptions"></div>
	
	</form>
</div>