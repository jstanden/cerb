<h2>Worker Permissions</h2>

<form>
	<label><input type="radio" name="enabled" value="1" onchange="if(this.checked)toggleDiv('configACL','block');genericAjaxGet('','c=config&a=handleSectionAction&section=acl&action=toggleACL&enabled=1');" {if $acl_enabled}checked="checked"{/if}> Enabled</label>
	<label><input type="radio" name="enabled" value="0" onchange="if(this.checked)toggleDiv('configACL','none');genericAjaxGet('','c=config&a=handleSectionAction&section=acl&action=toggleACL&enabled=0');" {if !$acl_enabled}checked="checked"{/if}> Disabled</label>
</form>

<table cellpadding="0" cellspacing="5" border="0" width="100%" id="configACL" style="display:{if !$acl_enabled}none{else}block{/if};">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<fieldset>
				<legend>
					Roles
				</legend>
				
				<ul style="margin:0;padding:0;list-style:none;">
					<li style="line-height:150%;">
						<form>
							<button type="button" onclick="genericAjaxGet('configRole','c=config&a=handleSectionAction&section=acl&action=getRole&id=0');"><span class="cerb-sprite sprite-add"></span> {'common.add'|devblocks_translate|capitalize}</button>
						</form>
					</li>
				{if !empty($roles)}
					{foreach from=$roles item=role key=role_id}
					<li style="line-height:150%;"><a href="javascript:;" onclick="genericAjaxGet('configRole','c=config&a=handleSectionAction&section=acl&action=getRole&id={$role_id}');">{$role->name}</a></li>
					{/foreach}
				{/if}
				</ul>
			</fieldset>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configRole">
				{include file="devblocks:cerberusweb.core::configuration/section/acl/edit_role.tpl" role=null}
			</form>
		</td>
		
	</tr>
</table>


