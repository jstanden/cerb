<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=groups&a=showInboxFilterPanel&id=0&group_id={$group_id}',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/funnel.gif{/devblocks_url}" align="top"> Add Inbox Filter</button>
</form>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabInbox">
<input type="hidden" name="group_id" value="{$group_id}">

{if !empty($team_rules)}
<div class="block">
<h2>Inbox Filters</h2>
<table cellspacing="2" cellpadding="0">
	{counter start=0 print=false}
	{foreach from=$team_rules item=rule key=rule_id name=rules}
		<tr>
			<!--
			<td valign="top">
				<input type="text" name="priorities[]" value="{counter}" size="4"><br>
			</td>
			 -->
			<td>
				<label><input type="checkbox" name="deletes[]" value="{$rule_id}">
				<input type="hidden" name="ids[]" value="{$rule_id}">
				<a href="javascript:;" onclick="genericAjaxPanel('c=groups&a=showInboxFilterPanel&id={$rule_id}&group_id={$group_id}',this,false,'550px');" style='color:rgb(0,120,0);font-weight:bold;'>{$rule->name|escape}</a>
				<br>
				
				<blockquote style="margin:2px;margin-left:20px;">
					{foreach from=$rule->criteria item=crit key=crit_key}
						{if $crit_key=='type'}
							Is a <b>{$crit.value}</b> message<br>
						{elseif $crit_key=='subject'}
							Subject = <b>{$crit.value}</b><br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='to'}
							{assign var=to_group_id value=$crit.value}
							To = <b>{$groups.$to_group_id->name}</b><br>
						{elseif $crit_key=='tocc'}
							To/Cc = <b>{$crit.value}</b><br>
						{elseif 'header'==substr($crit_key,0,6)}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif $crit_key=='attachment'}
							Attachment = <b>{$crit.value}</b><br>
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
					
					<blockquote style="margin:2px;margin-left:30px;font-size:90%;color:rgb(130,130,130);">
						{foreach from=$rule->actions item=action key=action_key}
							{if $action_key=="status"}
								{if $action.is_deleted==1}Delete Ticket{elseif $action.is_closed==1}Close Ticket{else}Open Ticket{/if}<br>
							{elseif $action_key=="move"}
								{assign var=g_id value=$action.group_id}
								{assign var=b_id value=$action.bucket_id}
								{if isset($groups.$g_id) && (0==$b_id || isset($buckets.$b_id))}
									Move to 
									<b>{$groups.$g_id->name}</b>:
									<b>{if 0==$b_id}Inbox{else}{$buckets.$b_id->name}{/if}</b>
								{/if}
								<br>
							{elseif $action_key=="assign"}
								{assign var=worker_id value=$action.worker_id}
								{if isset($workers.$worker_id)}
									Assign to <b>{$workers.$worker_id->getName()}</b><br>
								{/if}
							{elseif $action_key=="spam"}
								{if $action.is_spam}Report Spam{else}Mark Not Spam{/if}<br>
							{elseif 0==strcasecmp('cf_',substr($action_key,0,3))}
								{* [TODO] Custom Field Types *}
								{assign var=col value=$action_key|explode:'_'}
								{assign var=cf_id value=$col.1}
								
								{if isset($custom_fields.$cf_id)}
									Set 
									{assign var=cfield value=$custom_fields.$cf_id}
									{assign var=cfield_source value=$cfield->source_extension}
									{$source_manifests.$cfield_source->name}:{$custom_fields.$cf_id->name} 
									 = 
									{if is_array($action.value)}
										{foreach from=$action.value item=i name=vals}
										<b>{$i}</b>{if !$smarty.foreach.vals.last} and {/if}
										{/foreach}
									{else}
										<b>{$action.value}</b>
									{/if}
									<br>
								{/if}
							{/if}
						{/foreach}
					<span>(Matched {$rule->pos} new messages)</span><br>
					</blockquote>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected</button>
</div>
{else}
	<div class="block">
	<h2>No training data available</h2>
	<br>
	Use the Pile Sorter or Bulk Update in ticket worklists to teach the system how to sort your group's incoming mail.<br>
	</div>
{/if}
	
</form>