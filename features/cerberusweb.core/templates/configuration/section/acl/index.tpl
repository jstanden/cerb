<h2>Worker Roles</h2>

<form>
	<button type="button" onclick="genericAjaxGet('configRole','c=config&a=handleSectionAction&section=acl&action=getRole&id=0');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<fieldset>
				<legend>
					Roles
				</legend>
				
				<ul style="margin:0;padding:0;list-style:none;">
				{if !empty($roles)}
					{foreach from=$roles item=list_role key=list_role_id}
					<li style="line-height:150%;"><a href="javascript:;" onclick="genericAjaxGet('configRole','c=config&a=handleSectionAction&section=acl&action=getRole&id={$list_role_id}');">{$list_role->name}</a></li>
					{/foreach}
				{/if}
				</ul>
			</fieldset>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configRole">
				{include file="devblocks:cerberusweb.core::configuration/section/acl/edit_role.tpl" role=$role}
			</form>
		</td>
		
	</tr>
</table>


