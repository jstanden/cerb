{include file="$path/tickets/teamwork/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamRouting">
<input type="hidden" name="team_id" value="{$team->id}">

{if !empty($team_rules)}
<div class="block">
<h2>Team Inbox Routing</h2>
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
				{$rule->header|capitalize}: <b style='color:rgb(0,120,0);'>{$rule->pattern}</b></label><br>
				<!-- <input type="text" name="patterns[]" value="{$rule->pattern}" size="45">  -->
				<blockquote style="margin:2px;margin-left:30px;font-size:90%;color:rgb(130,130,130);">
				{foreach from=$rule->params item=v key=k name=params}
					{if $k == 'closed' && !empty($v)}
						{if $v}Close Ticket{else}Open Ticket{/if}<br>
					{elseif $k == 'priority' && !empty($v)}
						Set Priority to '{$v}'<br>
					{elseif $k == 'spam' && !empty($v)}
						{if $v=='N'}Mark Not Spam{else}Report Spam{/if}<br>
					{elseif $k == 'team' && !empty($v)}
						Move to '{$category_name_hash.$v}'<br>
					{/if}
				{/foreach}
				<span>(Matched {$rule->pos} new messages)</span><br>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
</div>
<br>
{else}
No team inbox routing is configured.<br>
{/if}
	
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected Rules</button>
</form>