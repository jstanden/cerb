<div id="tourConfigMailRouting"></div>
<div class="block" id="configMailboxRouting">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><h2>Mail Routing</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveRouting">
			
			<table cellpadding="2" cellspacing="0" border="0">
				<tr style="background-color:rgb(240, 240, 240);border-bottom:1px solid rgb(130, 130, 130);">
					<td nowrap="nowrap"><span style="font-weight:bold;margin:0px 5px 0px 5px;">Priority</span></td>
					<td nowrap="nowrap"><span style="font-weight:bold;margin:0px 5px 0px 5px;">Sent to:</span></td>
					<td></td>
					<td nowrap="nowrap"><span style="font-weight:bold;margin:0px 5px 0px 5px;">Deliver to inbox:</span></td>
					<td nowrap="nowrap"><span style="font-weight:bold;">Remove</span></td>
					<td></td>
				</tr>

 				{counter name="routing" start=0 print=false}

				{foreach from=$routing item=route key=route_id}
				{assign var=route_team_id value=$route->team_id}
				{assign var=team value=$teams.$route_team_id}
				<tr>
					<td width="0%" nowrap="nowrap">
						<input type="hidden" name="route_ids[]" value="{$route_id}">
						<input type="text" name="positions[]" value="{counter}" size="3">
					</td>
					<td width="0%" nowrap="nowrap">
						<input type="text" name="route_pattern[]" value="{$route->pattern}" size="35"></td>
					<td width="0%" nowrap="nowrap"> &#187; </td>
					<td width="0%" nowrap="nowrap">
						<!-- <span style="color:rgb(80,80,230);" id="mbox_routing_{$route->id}">{$team->name}</span>  -->
						<select name="route_team_id[]">
						{if !empty($teams)}
						{foreach from=$teams item=team key=team_id}
							<option value="{$team_id}" {if $team_id==$route_team_id}selected{/if}>{$team->name}
						{/foreach}
						{/if}
						</select>
					</td>
					<td width="0%" nowrap="nowrap" align="center">
						<input type="checkbox" name="route_remove[]" value="{$route->id}">
					</td>
					<td width="100%">
					</td>
				</tr>
				{/foreach}

			</table>
			<br>
			
			<b>Which group inbox should receive any unrouted mail?</b><br> 
			<select name="default_team_id">
				<option value="0">-- None (Bounce) --
			{if !empty($teams)}
			{foreach from=$teams item=team key=team_id}
				<option value="{$team_id}" {if $team->is_default}selected{/if}>{$team->name}
			{/foreach}
			{/if}
			</select><br>
			<br>

			<b>Add routing rule (Pattern -> Group):</b> <!-- [<a href="javascript:;">Explain</a>] --><br>
			{include file="$core_tpl/configuration/tabs/mail/mail_routing_add.tpl"} 
			<a href="javascript:;" onclick="genericAjaxGet('configMailRoutingAdd','c=config&a=getMailRoutingAdd',configAjax.cbMailRoutingAdd);">add another rule field</a>
			<br>
			<div id="configMailRoutingAdd"></div>
			<b>Routing pattern tips:</b><br>
			<ul style="margin-top:0px;">
				<li>The easiest pattern is a single address: <i style="color:rgb(0,180,0);">support@webgroupmedia.com</i></li>
				<li>Use * for wildcards: <i style="color:rgb(0,180,0);">support@*</i></li>
				<li>A single rule may contain sets: <i style="color:rgb(0,180,0);">(support|help|contact)@*</i></li>
			</ul>
			<br>
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>