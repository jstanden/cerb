<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=config&a=showPreParserPanel&id=0',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> Add Mail Filter</button>
</form>

{if !empty($filters)}
<div class="block" id="configMailPreparseFilters">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParseFilters">
<h2>Mail Filters</h2>

<table cellspacing="2" cellpadding="0">
	<tr>
		<td align="center" style="padding-right:10px;"><b>{$translate->_('common.order')|capitalize}</b></td>
		<td><b>Incoming Mail Filter</b></td>
		<td align="center"><b>{$translate->_('common.remove')|capitalize}</b></td>
	</tr>
	
	{counter start=0 print=false}
	{foreach from=$filters item=filter key=filter_id name=filters}
	<tr>
		<td valign="top" align="center">
			{if $filter->is_sticky}
				<input type="hidden" name="sticky_ids[]" value="{$filter_id}">
				<input type="text" name="sticky_order[]" value="{counter name=order}" size="2" maxlength="2">
			{else}
				<i><span style="color:rgb(180,180,180);font-size:80%;">(auto)</span></i>
			{/if}
		</td>
		<td valign="top" style="{if $filter->is_sticky}background-color:rgb(255,255,221);border:2px solid rgb(255,215,0);{else}{/if}padding:5px;">
			<a href="javascript:;" onclick="genericAjaxPanel('c=config&a=showPreParserPanel&id={$filter_id}',this,false,'550px');" style="color:rgb(0,120,0);font-weight:bold;">{$filter->name|escape}</a><br>
			{foreach from=$filter->criteria item=crit key=crit_key}
				{if $crit_key=='tocc'}
					To/Cc = <b>{$crit.value}</b><br>
				{elseif $crit_key=='type'}
					Is a <b>{$crit.value}</b> message<br>
				{elseif $crit_key=='from'}
					From = <b>{$crit.value}</b><br>
				{elseif $crit_key=='dayofweek'}
					Day of Week is 
						{foreach from=$crit item=day name=timeofday}
						<b>{$day}</b>{if !$smarty.foreach.timeofday.last} or {/if}
						{/foreach}
						<br>
				{elseif $crit_key=='timeofday'}
					{assign var=from_time value=$crit.from|explode:':'}
					{assign var=to_time value=$crit.to|explode:':'}
					Time of Day 
						<i>between</i> 
						<b>{$from_time.0|string_format:"%d"}:{$from_time.1|string_format:"%02d"}</b> 
						<i>and</i> 
						<b>{$to_time.0|string_format:"%d"}:{$to_time.1|string_format:"%02d"}</b> 
						<br>
				{elseif $crit_key=='header1'}
					Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
				{elseif $crit_key=='header2'}
					Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
				{elseif $crit_key=='header3'}
					Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
				{elseif $crit_key=='header4'}
					Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
				{elseif $crit_key=='header5'}
					Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
				{elseif $crit_key=='body'}
					Body = <b>{$crit.value}</b><br>
				{elseif $crit_key=='body_encoding'}
					Body Charset = <b>{$crit.value}</b><br>
				{elseif $crit_key=='attachment'}
					Attachment = <b>{$crit.value}</b><br>
				{elseif 0==strcasecmp('cf_',substr($crit_key,0,3))}
					{include file="$core_tpl/internal/custom_fields/filters/render_criteria_list.tpl"}
				{/if}
			{/foreach}
			
			<blockquote style="margin:2px;margin-left:20px;font-size:95%;color:rgb(100,100,100);">
			{foreach from=$filter->actions item=action key=action_key}
				{if $action_key=="blackhole"}
					Blackhole<br>
				{elseif $action_key=="redirect"}
					Redirect to <b>{$action.to}</b><br>
				{elseif $action_key=="bounce"}
					Bounce<br>
				{/if}
			{/foreach}
			<span>(Matched {$filter->pos} incoming messages)</span><br>
			
			</blockquote>
		</td>
		<td valign="top" align="center">
			<label><input type="checkbox" name="deletes[]" value="{$filter_id}">
			<input type="hidden" name="ids[]" value="{$filter_id}">
		</td>
	</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>
</div>
<br>
{/if} {* endif filters exist *}
