<table cellpadding="2" cellspacing="0" width="200" border="0" class="tableGreen">
	<tr>
		<th class="tableThGreen"><img src="images/find.gif"> Search Criteria</th>
	</tr>
	<tr style="border-bottom:1px solid rgb(200,200,200);">
		<td>
			<a href="javascript:;" onclick="">reset criteria</a> |
			<a href="#">save</a> |
			<a href="#">load</a>
		</td>
	</tr>
	<tr>
		<td style="background-color:rgb(255,255,255);">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td colspan="2" align="left">
						<a href="javascript:;" onclick="addCriteria('{$divName}');">Add new criteria</a> 
						<a href="javascript:;" onclick="addCriteria('{$divName}');"><img src="images/data_add.gif" align="absmiddle" border="0"></a> 
					</td>
				</tr>
				{foreach from=$params item=param}
				{if $param->field=="t.status"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.status')} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{$p}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{elseif $param->field=="t.priority"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.priority')} 
							{$param->operator}
							{foreach from=$param->value item=p name=params}
							<b>{$p}</b>
							 {if !$smarty.foreach.params.last} or {/if}
							{/foreach}
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{elseif $param->field=="t.subject"}
					<tr>
						<td width="100%">
							<img src="images/data_find.gif" align="absmiddle"> 
							{$translate->say('ticket.subject')} 
							{$param->operator} 
							<b>{$param->value}</b>
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="#"><img src="images/data_error.gif" border="0" align="absmiddle"></a></td>
					</tr>
				{else}
				{/if}
				{/foreach}
			</table>
		</td>
	</tr>
</table>