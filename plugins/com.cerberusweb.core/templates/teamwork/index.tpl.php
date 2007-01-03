<h1>Teamwork</h1>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<table cellpadding="2" cellspacing="0" border="0%" width="150" class='tableGreen'>	
				<tr class="tableThGreen">
					<th nowrap="nowrap"><img src="images/folder_gear.gif"> My Teams <a href="#" class="normalLink">reload</a>&nbsp;</th>
				</tr>
				<tr class="tableCellBg">
					<td nowrap="nowrap"><b style="color:rgb(229,95,0);">Assign me work from:</b></td>
				</tr>
				
				{foreach from=$teams item=team name=teams}
				<tr class="tableCellBg">
					<td nowrap="nowrap">
						<label><input type="checkbox"> <img src="images/businessmen.gif" border="0"> 
						<b>{$team->name}</b></label> (0)
					</td>
				</tr>
				{/foreach}
				
				<tr class="tableCellBg">
					<td nowrap="nowrap"><b style="color:rgb(229,95,0);">How many tickets?</b></td>
				</tr>
				<tr class="tableCellBg">
					<td nowrap="nowrap">
						<select name="">
							<option value="1">1
							<option value="5" selected>5
							<option value="10">10
							<option value="25">25
						</select><input type="button" value="Assign">
					</td>
				</tr>
			</table>
		</td>
		<td width="0%" nowrap="nowrap" valign="top"><img src="images/spacer.gif" width="5" height="1"></td>
		<td width="100%" valign="top">
			
			<table cellpadding="0" cellspacing="0" width="100%" class="tableBlue">
				<tr>
					<td class="tableThBlue"> <img src="images/scroll_view.gif" align="absmiddle" border="0"> My Tickets ({$count} assigned)</td>
				</tr>
			</table>
			
			<table cellpadding="3" cellspacing="0" width="100%">
			
			{foreach from=$mytickets item=result name=mytickets}
				<tr>
					<td width="0%" nowrap="nowrap" valign="top">
						{if $result.t_priority == 100}
							<img src="images/star_red.gif" title="{$result.t_priority}" align="absmiddle">
						{elseif $result.t_priority >= 90}
							<img src="images/star_yellow.gif" title="{$result.t_priority}" align="absmiddle">
						{elseif $result.t_priority >= 75}
							<img src="images/star_green.gif" title="{$result.t_priority}" align="absmiddle">
						{elseif $result.t_priority >= 50}
							<img src="images/star_blue.gif" title="{$result.t_priority}" align="absmiddle">
						{elseif $result.t_priority >= 25}
							<img src="images/star_grey.gif" title="{$result.t_priority}" align="absmiddle">
						{else}
							<img src="images/star_alpha.gif" title="{$result.t_priority}" align="absmiddle">
						{/if}
						
						<img src="images/flag_red.gif" align="absmiddle" title="Assigned">
					</td>
					<td width="100%" style="font-size:11px;color:rgb(120,120,120);">
						<a href="index.php?c=core.module.dashboard&a=viewticket&id={$result.t_id}" class="ticket">{$result.t_subject}</a> #{$result.t_mask}<br>
						Updated {$result.t_updated_date|date_format} by {$result.t_last_wrote}<br>

						<div id="teamworkRender{$result.t_id}"></div>

						<div style="margin:5px;">
						<a href="javascript:;" onclick=""><img src="images/window_view.gif" border="0" align="absmiddle" title="Preview the latest message"></a>  
						<a href="javascript:;" onclick=""><img src="images/gear.gif" border="0" align="absmiddle" title="Modify ticket"></a> 
						<a href="javascript:;" onclick=""><img src="images/document_down.gif" border="0" align="absmiddle" title="Release ticket (unassign from me)"></a> 
						<a href="javascript:;" onclick=""><img src="images/document_error.gif" border="0" align="absmiddle" title="Close ticket (with reason)"></a> 
						</div> 
					</td>
				</tr>
				{if !$smarty.foreach.mytickets.last}
				<tr>
					<td colspan="2" style="border-bottom:1px solid rgb(230,230,230);"><img src="images/spacer.gif" width="1" height="1"></td>
				</tr>
				{/if}
			{/foreach}
			
			</table>
			
		</td>
	</tr>
</table>

<br>

{include file="file:$path/dashboards/whos_online.tpl.php"}