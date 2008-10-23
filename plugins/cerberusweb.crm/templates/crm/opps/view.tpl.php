{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">jump to actions</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a>
			<!-- {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=contacts&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} -->
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="bob_id"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=o_id');">{$translate->_('contact_org.id')|capitalize}</a>
			{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.o_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.o_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">{if $result.o_is_closed && $result.o_is_won}<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/up_plus_gray.gif{/devblocks_url}" align="top" title="Won"> {elseif $result.o_is_closed && !$result.o_is_won}<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/down_minus_gray.gif{/devblocks_url}" align="top" title="Lost"> {/if}<a href="{devblocks_url}c=crm&a=browseOpps&id={$result.o_id}&view_id={$view->id}{/devblocks_url}" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.o_id}_{$view->id}">{$result.o_name|escape}</b></a> <a href="javascript:;" onclick="genericAjaxPanel('c=crm&a=showOppPanel&view_id={$view->id}&id={$result.o_id}', this, false, '500px');" style="color:rgb(180,180,180);font-size:90%;">(peek)</a></td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="o_id"}
				<td>{$result.o_id}&nbsp;</td>
			{elseif $column=="org_name"}
				<td>
					{if !empty($result.org_id)}
						<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id={$result.org_id}&view_id={$view->id}',this,false,'500px',ajax.cbOrgCountryPeek);">{$result.org_name}</a>&nbsp;
					{/if}
				</td>
			{elseif $column=="a_email"}
				<td>
					{if !empty($result.a_email)}
						<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.a_email}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" title="{$result.a_email}">{$result.a_email}</a>&nbsp;
					{else}
						<!-- [<a href="javascript:;">assign</a>]  -->
					{/if}
				</td>
			{elseif $column=="o_created_date"}
				<td>{$result.o_created_date|devblocks_date}&nbsp;</td>
			{elseif $column=="o_updated_date"}
				<td>{$result.o_updated_date|devblocks_date}&nbsp;</td>
			{elseif $column=="o_closed_date"}
				<td>{$result.o_closed_date|devblocks_date}&nbsp;</td>
			{elseif $column=="o_worker_id"}
				<td>
					{assign var=o_worker_id value=$result.o_worker_id}
					{if isset($workers.$o_worker_id)}
						{$workers.$o_worker_id->getName()}&nbsp;
					{/if}
				</td>
			{elseif $column=="o_campaign_id"}
				{assign var=o_campaign_id value=$result.o_campaign_id}
				<td>
					{* [TODO] Only hyperlink if permitted to edit campaign *}
					{if isset($campaigns.$o_campaign_id)}
						<a href="javascript:;" onclick="genericAjaxPanel('c=crm&a=showCampaignPanel&id={$o_campaign_id}&view_id={$view->id}',this,false,'500px');">{$campaigns.$o_campaign_id->name}</a>&nbsp;
					{/if}
				</td>
			{elseif $column=="o_campaign_bucket_id"}
				{assign var=o_campaign_id value=$result.o_campaign_id}
				{assign var=o_bucket_id value=$result.o_campaign_bucket_id}
				{assign var=buckets value=$campaign_buckets.$o_campaign_id}
				<td>
					{if empty($o_bucket_id)}
						Inbox
					{elseif isset($buckets.$o_bucket_id)}
						{$buckets.$o_bucket_id->name}
					{/if}
				</td>
			{elseif $column=="o_amount"}
				<td>{$result.o_amount} ({$result.o_probability}%)</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{if !empty($campaigns)}
			<select name="bucket_id" onchange="this.form.a.value='viewOppSetCampaign';this.form.submit();">
				<option value="">-- set bucket --</option>
				<optgroup label="Campaigns">
				{foreach from=$campaigns item=campaign key=campaign_id}
					<option value="c{$campaign_id}_b0">{$campaign->name}</option>
				{/foreach}
				</optgroup>
				
				{foreach from=$campaigns item=campaign key=campaign_id}
				{if isset($campaign_buckets.$campaign_id)}
					<optgroup label="-- {$campaign->name|escape} --">
						{foreach from=$campaign_buckets.$campaign_id item=bucket key=bucket_id}
							<option value="c{$campaign_id}_b{$bucket_id}">{$bucket->name}</option>
						{/foreach}
					</optgroup>
				{/if}
				{/foreach}
			</select>
			{/if}
			
			{if !empty($workers)}
			<select name="worker_id" onchange="this.form.a.value='viewOppSetWorker';this.form.submit();">
				<option value="">-- set worker --</option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}">{$worker->getName()}</option>
				{/foreach}
				<option value="0">- unassign -</option>
			</select>
			{/if}
			
			<button type="button" onclick="this.form.a.value='viewOppDelete';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> Delete</button>
		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>