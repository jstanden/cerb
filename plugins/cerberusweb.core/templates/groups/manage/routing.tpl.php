{include file="$path/groups/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTeamRouting">
<input type="hidden" name="team_id" value="{$team->id}">

{if !empty($team_rules)}
<div class="block">
<h2>Inbox Auto-Sorting</h2>
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
					{if $rule->do_status != ''}
						{if $rule->do_status==1}Close Ticket{elseif $rule->do_status==0}Open Ticket{elseif $rule->do_status==2}Delete Ticket{/if}<br>
					{/if}
					{if $rule->do_spam != ''}
						{if $rule->do_spam=='N'}Mark Not Spam{else}Report Spam{/if}<br>
					{/if}
					{if $rule->do_move != ''}
						{assign var=move_code value=$rule->do_move}
						Move to '{$category_name_hash.$move_code}'<br>
					{/if}
				<span>(Matched {$rule->pos} new messages)</span><br>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected Rules</button>
</div>
{else}
	<div class="block">
	<h2>No training data available</h2>
	<br>
	Use 'Auto-Assist' or 'Bulk Update' in ticket lists to teach the system how to sort your group's incoming mail.<br>
	</div>
{/if}
	
</form>