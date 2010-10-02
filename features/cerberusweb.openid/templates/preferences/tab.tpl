<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="cerberusweb.openid.preferences.tab">

<fieldset>
<legend>Your OpenID Identities</legend>	
<ul style="margin:5px 0px 0px 10px;padding:0px;list-style:none;">
	{foreach from=$openids item=openid key=openid_id}
	<li style="padding-bottom:10px;">
		<input type="hidden" name="openid_claimed_ids[]" value="{$openid->openid_claimed_id|escape}">
		<button type="button" onclick="if(confirm('Are you sure you want to delete this OpenID identity?')) { $(this).closest('li').remove(); genericAjaxGet('','c=openid.ajax&a=deletePref&id={$openid_id}'); }"><span class="cerb-sprite sprite-forbidden"></span></button>
		<img src="{devblocks_url}c=resource&p=cerberusweb.openid&f=images/openid-inputicon.gif{/devblocks_url}" align="top"> 
		{$openid->openid_url}
	</li>
	{/foreach}
	<li style="padding-bottom:10px;">
		<b>Add another OpenID identity:</b><br>
		<input type="text" name="openid_url" size="45" style="background:url('{devblocks_url}c=resource&p=cerberusweb.openid&f=images/openid-inputicon.gif{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;">
	</li>
</ul>
</fieldset>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>

</form>
