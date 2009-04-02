<div id="tourMyAcctWatchers"></div>
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id=0',null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> Add Notification</button>
</form>

{if !empty($filters)}
<div class="block" id="myAcctWatchers">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="preferences">
	<input type="hidden" name="a" value="saveTab">
	<input type="hidden" name="ext_id" value="core.pref.notifications">

	<h2>{$translate->_('watchers.ui.pref.mail_forwarding')}</h2>
	
	<table cellspacing="2" cellpadding="2">
		{counter start=0 print=false name=order}
		{foreach from=$filters item=filter key=filter_id name=filters}
			<tr>
				<td valign="top" align="center">
					<label><input type="checkbox" name="deletes[]" value="{$filter_id}">
					<input type="hidden" name="ids[]" value="{$filter_id}">
				</td>
				<td style="padding:5px;">
					<a href="javascript:;" onclick="genericAjaxPanel('c=preferences&a=handleTabAction&tab=core.pref.notifications&action=showWatcherPanel&id={$filter_id}',null,false,'550px');" style="color:rgb(0,120,0);font-weight:bold;">{$filter->name|escape}</a>
					<br>
					
					{foreach from=$filter->criteria item=crit key=crit_key}
						{if $crit_key=='event'}
							Event =
								{foreach from=$crit key=event item=null name=events}
								{if 'mail_incoming'==$event}
									<b>Incoming mail</b>
								{elseif 'mail_outgoing'==$event}
									<b>Outgoing mail</b>
								{elseif 'ticket_assignment'==$event}
									<b>Ticket assignment</b>
								{elseif 'ticket_comment'==$event}
									<b>Ticket comment</b>
								{/if}
								{if !$smarty.foreach.events.last} or {/if}
								{/foreach}
							<br>
						{elseif $crit_key=='groups'}
							Group/Bucket = 
							{foreach from=$crit.groups key=group_id item=bucket_ids name=groups}
								{if isset($groups.$group_id)}
									<b>{$groups.$group_id->name}</b>
								
									{if is_array($bucket_ids) && !empty($bucket_ids)}
									(<i>{foreach from=$bucket_ids item=bucket_id name=buckets}{if 0==$bucket_id}{$translate->_('common.inbox')|capitalize}{else}{$buckets.$bucket_id->name}{/if}{if !$smarty.foreach.buckets.last}, {/if}{/foreach}</i>)
									{/if}
								{/if}
								{if !$smarty.foreach.groups.last} or {/if}
							{/foreach}
							<br>
						{elseif $crit_key=='next_worker_id'}
							{assign var=worker_id value=$crit.value}
							{if isset($workers.$worker_id)}
							Assigned to = 
								<b>{$workers.$worker_id->getName()}</b>
							<br>
							{/if}
						{elseif $crit_key=='subject'}
							Subject = <b>{$crit.value}</b><br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif 'header'==substr($crit_key,0,6)}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
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
						{elseif 0==strcasecmp('cf_',substr($crit_key,0,3))}
							{* [TODO] Custom Field Types *}
							{assign var=col value=$crit_key|explode:'_'}
							{assign var=cf_id value=$col.1}
							
							{if isset($custom_fields.$cf_id)}
								{assign var=cfield value=$custom_fields.$cf_id}
								{assign var=crit_oper value=$crit.oper}
								{assign var=cfield_source value=$cfield->source_extension}
								{$source_manifests.$cfield_source->name}:{$custom_fields.$cf_id->name} 
								{if isset($crit.value) && is_array($crit.value)}
									 = 
									{foreach from=$crit.value item=i name=vals}
									<b>{$i}</b>{if !$smarty.foreach.vals.last} or {/if}
									{/foreach}
								{elseif 'E'==$cfield->type}
									<i>between</i> <b>{$crit.from}</b> <i>and</i> <b>{$crit.to}</b>
								{else}
									{if !empty($crit_oper)}{$crit_oper}{else}={/if}
									<b>{$crit.value}</b>
								{/if}
								<br>
							{/if}
						{/if}
					{/foreach}
					
					<blockquote style="margin:2px;margin-left:20px;font-size:95%;color:rgb(100,100,100);">
						{foreach from=$filter->actions item=action key=action_key}
							{if $action_key=="email" && isset($action.to) && !empty($action.to)}
								Forward to 
								{if is_array($action.to)}
									{foreach from=$action.to item=email name=emails} 
									<b>{$email}</b>{if !$smarty.foreach.emails.last}, {/if}
									{/foreach}
								{else}
									<b>{$action.to}</b>
								{/if}
								<br>
							{/if}
						{/foreach}
					<span>(Matched {$filter->pos} messages)</span><br>
					</blockquote>
				</td>
			</tr>
		{/foreach}
	</table>
	<br>	

	{if !empty($filters)}<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
	</form>
</div>
<br>
{/if}
