<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewAutoAssist">
<input type="hidden" name="view_id" value="{$view_id}">

<H3 style="font-size:18px;margin:0px;">Assist me with sorting:</H3>
Based on:  
{if !empty($tips)}<label><input type="radio" name="based_on" value="recent" onclick="toggleDiv('{$view_id}_recent','block');toggleDiv('{$view_id}_piles','none');" checked>my recent actions</label>{/if}
{if !empty($biggest)}<label><input type="radio" name="based_on" value="piles" onclick="toggleDiv('{$view_id}_piles','block');toggleDiv('{$view_id}_recent','none');" {if empty($tips)}checked{/if}>the biggest piles of work</label>{/if}
<br>
<br>

{if !empty($tips)}
<div id="{$view_id}_recent" style="display:block;">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td align="top" colspan="2">
		<H3 style="font-size:18px;margin:0px;">Recently you've done these actions the most frequently:</H3>
	</td>
</tr>
<tr>
	<td align="center" nowrap>Always</td>
	<td>Repeat for all tickets in this list</td>
</tr>
{foreach from=$tips item=stats key=hash}
<tr>
	<td align="center" nowrap="nowrap">
		<input type="checkbox" name="always[]" value="{$hash}">
	</td>
	<td align="top">
		{assign var=move_code value=$stats[2]}
		{assign var=move_to_name value=$category_name_hash.$move_code}
		<input type="hidden" name="hashes[]" value="{$hash}">
		<label><input type="checkbox" name="repeat[]" value="{$hash}"> {$stats[0]} <span style="color:rgb(0,120,0);" title="{$stats[1]|escape:"htmlall"}">{$stats[1]|truncate:45:'...'}</span> moved to <b>{$move_to_name}</b> ({$stats[3]} times)</label><br>
	</td>
</tr>
{/foreach}
</table>
<br>
</div>
{/if}

{if !empty($biggest)}
<div id="{$view_id}_piles" style="display:{if empty($tips)}block{else}none{/if};">
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td align="top" colspan="3">
		<H3 style="font-size:18px;margin:0px;">The biggest concentrations of tickets in this list are:</H3>
	</td>
</tr>
<tr>
	<td width="0%" nowrap align="center">Always</td>
	<td width="0%" nowrap>Move to:</td>
	<td width="100%">From biggest pile:</td>
</tr>
{foreach from=$biggest item=stats key=hash}
<tr>
	<td width="0%" nowrap="nowrap" align="center">
		<input type="checkbox" name="piles_always[]" value="{$hash}">
	</td>
	<td width="0%" nowrap="nowrap">
		<select name="piles_moveto[]">
			<option value=""></option>
			{foreach from=$team_categories item=team_category_list key=teamId}
				{assign var=team value=$teams.$teamId}
				{if $dashboard_team_id == $teamId}
					<optgroup label="-- {$team->name} --">
					{foreach from=$team_category_list item=category}
						<option value="c{$category->id}">{$category->name}</option>
					{/foreach}
					</optgroup>
				{/if}
			{/foreach}
			<optgroup label="Team Inboxes" style="">
				{foreach from=$teams item=team}
					<option value="t{$team->id}">{$team->name}</option>
				{/foreach}
			</optgroup>
		</select>
	</td>
	<td width="100%" align="top">
		<input type="hidden" name="piles_hash[]" value="{$hash}">
		<input type="hidden" name="piles_type[]" value="{$stats[0]}">
		<input type="hidden" name="piles_value[]" value="{$stats[1]|escape:"htmlall"}">
		<label>{$stats[0]} <span style="color:rgb(0,120,0);" title="{$stats[1]|escape:"htmlall"}">{$stats[1]|truncate:45:'...'}</span> ({$stats[2]} hits)</label><br>
	</td>
</tr>
{/foreach}
</table>
<br>
</div>
{/if}

<button type="button" onclick="this.form.submit();" style="">Perform selected actions</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style="">Do nothing</button>

</form>
