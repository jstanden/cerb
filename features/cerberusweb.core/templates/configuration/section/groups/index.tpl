<h2>{'common.groups'|devblocks_translate|capitalize}</h2>

<form action="#" onsubmit="return false;">
	<button type="button" onclick="genericAjaxGet('configTeam','c=config&a=handleSectionAction&section=groups&action=getGroup&id=0');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Add Group</button>
</form>

<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<fieldset>
				<legend>{'common.groups'|devblocks_translate|capitalize}</legend>
				
				<ul style="list-style:none;margin:0px;padding-left:0px;">
				{if !empty($teams)}
					{foreach from=$teams item=team key=team_id}
					<li style="padding:2px;"><a href="javascript:;" onclick="genericAjaxGet('configTeam','c=config&a=handleSectionAction&section=groups&action=getGroup&id={$team->id}');">{$team->name}</a></li>
					{/foreach}
				{/if}
				</ul>
			</fieldset>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configTeam">
				{include file="devblocks:cerberusweb.core::configuration/section/groups/edit_group.tpl" team=null}
			</form>
		</td>
		
	</tr>
</table>


