<div class="block">

<h2>{$translate->_('webapi.ui.cfg.access_keys')}</h2>
{$translate->_('webapi.ui.cfg.access_keys_about')}<br>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="webapi.config.tab">
<input type="hidden" name="plugin_id" value="{$plugin->id}">

{foreach from=$access_keys item=access_key key=access_key_id}
	<label>
		<b style="color:rgb(0,120,0);">{$access_key->nickname}</b><br>
		<input type="checkbox" name="deletes[]" value="{$access_key_id}">{$translate->_('webapi.ui.cfg.delete_key')}
	</label><br> 
	
	<div style="margin-left:20px;margin-bottom:10px;">
		<b>{$translate->_('webapi.ui.cfg.access_key')}</b><br>
		<input type="hidden" name="access_ids[]" value="{$access_key_id}">
		<div style="margin-left:15px;margin-bottom:5px;">
			<i>{$access_key->access_key}</i>
			(<a href="javascript:;" onclick="toggleDiv('secret{$access_key_id}');">{$translate->_('webapi.ui.cfg.secret_key')}</a>)
			
			<div style="display:none;" id="secret{$access_key_id}">
				<span style="background-color:rgb(255,220,220);"><b>{$translate->_('webapi.ui.cfg.secret_key_label')}</b> <i>{$access_key->secret_key}</i></span><br>
			</div>
		</div>
		
		<b>{$translate->_('webapi.ui.cfg.rights')}</b><br>
		<div style="margin-left:15px;margin-bottom:5px;">
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td>{$translate->_('webapi.ui.cfg.address_book_people')}</td>
					<td>
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="0" {if !$access_key->rights.acl_addresses}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="1" {if 1==$access_key->rights.acl_addresses}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclAddresses{$access_key_id}" value="2" {if 2==$access_key->rights.acl_addresses}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td>{$translate->_('webapi.ui.cfg.address_book_orgs')}</td>
					<td>
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="0" {if !$access_key->rights.acl_orgs}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="1" {if 1==$access_key->rights.acl_orgs}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclOrgs{$access_key_id}" value="2" {if 2==$access_key->rights.acl_orgs}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td>{$translate->_('webapi.ui.cfg.tickets')}</td>
					<td>
						<label><input type="radio" name="aclTickets{$access_key_id}" value="0" {if !$access_key->rights.acl_tickets}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclTickets{$access_key_id}" value="1" {if 1==$access_key->rights.acl_tickets}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclTickets{$access_key_id}" value="2" {if 2==$access_key->rights.acl_tickets}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td>{$translate->_('webapi.ui.cfg.tasks')}</td>
					<td>
						<label><input type="radio" name="aclTasks{$access_key_id}" value="0" {if !$access_key->rights.acl_tasks}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclTasks{$access_key_id}" value="1" {if 1==$access_key->rights.acl_tasks}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclTasks{$access_key_id}" value="2" {if 2==$access_key->rights.acl_tasks}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td>{$translate->_('common.fnr')}</td>
					<td>
						<label><input type="radio" name="aclFnr{$access_key_id}" value="0" {if !$access_key->rights.acl_fnr}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclFnr{$access_key_id}" value="1" {if 1==$access_key->rights.acl_fnr}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclFnr{$access_key_id}" value="2" {if 2==$access_key->rights.acl_fnr}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td>{$translate->_('webapi.ui.cfg.parser')}</td>
					<td>
						<label><input type="radio" name="aclParser{$access_key_id}" value="0" {if !$access_key->rights.acl_parser}checked{/if}> {$translate->_('webapi.ui.cfg.none')}</label> 
						<label><input type="radio" name="aclParser{$access_key_id}" value="1" {if 1==$access_key->rights.acl_parser}checked{/if}> {$translate->_('webapi.ui.cfg.read_only')}</label> 
						<label><input type="radio" name="aclParser{$access_key_id}" value="2" {if 2==$access_key->rights.acl_parser}checked{/if}> {$translate->_('webapi.ui.cfg.change')}</label> 
					</td>
				</tr>
				<tr>
					<td valign="top">{$translate->_('webapi.ui.cfg.kb_topics_visible')}</td>
					<td>
						{foreach from=$kb_topics item=kb_topic key=kb_topic_id}
							<label><input type="checkbox" name="aclKB{$access_key_id}[]" value="{$kb_topic_id}" {if 1==$access_key->rights.acl_kb_topics[$kb_topic_id]}checked{/if}> {$kb_topic->name}</label><br> 
						{/foreach}
					</td>
				</tr>
			</table>
		</div>
		
		<b>{$translate->_('webapi.ui.cfg.restrict_ips')}</b> ({$translate->_('common.optional')|lower})<br>
		<div style="margin-left:15px;margin-bottom:5px;">
			<input type="text" name="ips{$access_key_id}" size="64" value="{foreach from=$access_key->rights.ips item=ip name=ips}{$ip}{if !$smarty.foreach.ips.last}, {/if}{/foreach}"><br>
			{$translate->_('webapi.ui.cfg.restrict_ips_hint')}
		</div>
		
	</div>
{/foreach}

<h2>{$translate->_('webapi.ui.cfg.add_key')}</h2>
<b>{$translate->_('webapi.ui.cfg.nickname')}</b> {$translate->_('webapi.ui.cfg.nickname_hint')}<br>
<input type="text" name="add_nickname" size="64" maxlength="64"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>

</form>

</div>