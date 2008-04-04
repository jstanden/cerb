<div class="block">

<h2>Access Keys</h2>
Access keys allow developers or applications to communicate directly with the helpdesk and bypass the user interface. 
Don't share your secret keys with people you don't trust, as they may permit  
remote control of your helpdesk.<br>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="webapi.config.tab">
<input type="hidden" name="plugin_id" value="{$plugin->id}">

{foreach from=$access_keys item=access_key key=access_key_id}
	<label>
		<input type="checkbox" name="deletes[]" value="{$access_key_id}">
		<b style="color:rgb(0,120,0);">{$access_key->nickname}</b>
	</label><br> 
	
	<div style="margin-left:20px;margin-bottom:10px;">
		<b>Access Key:</b><br>
		<input type="hidden" name="access_ids[]" value="{$access_key_id}">
		<div style="margin-left:15px;margin-bottom:5px;">
			<i>{$access_key->access_key}</i>
			(<a href="javascript:;" onclick="toggleDiv('secret{$access_key_id}');">secret key</a>)
			
			<div style="display:none;" id="secret{$access_key_id}">
				<span style="background-color:rgb(255,220,220);"><b>Secret Key:</b> <i>{$access_key->secret_key}</i></span><br>
			</div>
		</div>
		
		<b>Rights:</b><br>
		<div style="margin-left:15px;margin-bottom:5px;">
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td>Address Book (People):</td>
					<td>
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="0" {if !$access_key->rights.acl_addresses}checked{/if}> None</label> 
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="1" {if 1==$access_key->rights.acl_addresses}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="2" {if 2==$access_key->rights.acl_addresses}checked{/if}> Change</label> 
					</td>
				</tr>
				<tr>
					<td>Address Book (Orgs):</td>
					<td>
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="0" {if !$access_key->rights.acl_orgs}checked{/if}> None</label> 
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="1" {if 1==$access_key->rights.acl_orgs}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="2" {if 2==$access_key->rights.acl_orgs}checked{/if}> Change</label> 
					</td>
				</tr>
				<tr>
					<td>Fetch &amp; Retrieve:</td>
					<td>
						<label><input type="radio" name="aclFnr{$access_key_id}" value="0" {if !$access_key->rights.acl_fnr}checked{/if}> None</label> 
						<label><input type="radio" name="aclFnr{$access_key_id}" value="1" {if 1==$access_key->rights.acl_fnr}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclFnr{$access_key_id}" value="2" {if 2==$access_key->rights.acl_fnr}checked{/if}> Change</label> 
					</td>
				</tr>
				<tr>
					<td>Parser:</td>
					<td>
						<label><input type="radio" name="aclParser{$access_key_id}" value="0" {if !$access_key->rights.acl_parser}checked{/if}> None</label> 
						<label><input type="radio" name="aclParser{$access_key_id}" value="1" {if 1==$access_key->rights.acl_parser}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclParser{$access_key_id}" value="2" {if 2==$access_key->rights.acl_parser}checked{/if}> Change</label> 
					</td>
				</tr>
				<tr>
					<td>Tickets:</td>
					<td>
						<label><input type="radio" name="aclTickets{$access_key_id}" value="0" {if !$access_key->rights.acl_tickets}checked{/if}> None</label> 
						<label><input type="radio" name="aclTickets{$access_key_id}" value="1" {if 1==$access_key->rights.acl_tickets}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclTickets{$access_key_id}" value="2" {if 2==$access_key->rights.acl_tickets}checked{/if}> Change</label> 
					</td>
				</tr>
				<tr>
					<td>Messages:</td>
					<td>
						<label><input type="radio" name="aclMessages{$access_key_id}" value="0" {if !$access_key->rights.acl_messages}checked{/if}> None</label> 
						<label><input type="radio" name="aclMessages{$access_key_id}" value="1" {if 1==$access_key->rights.acl_messages}checked{/if}> Read Only</label> 
						<label><input type="radio" name="aclMessages{$access_key_id}" value="2" {if 2==$access_key->rights.acl_messages}checked{/if}> Change</label> 
					</td>
				</tr>
			</table>
		</div>
		
		<b>Restrict to IPs:</b> (optional)<br>
		<div style="margin-left:15px;margin-bottom:5px;">
			<input type="text" name="ips{$access_key_id}" size="64" value="{foreach from=$access_key->rights.ips item=ip name=ips}{$ip}{if !$smarty.foreach.ips.last}, {/if}{/foreach}"><br>
			(comma-delimited, partial masks OK)
		</div>
		
	</div>
{/foreach}

<h2>Add Access Key</h2>
<b>Nickname:</b> (e.g., Jeff's Developer Key)<br>
<input type="text" name="add_nickname" size="64" maxlength="64"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>

</form>

</div>