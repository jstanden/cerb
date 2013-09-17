<table cellpadding="0" cellspacing="0" border="0" class="sidebar" id="history_sidebar">
	<tr>
		<th>{'portal.sc.public.my_account'|devblocks_translate|capitalize}</th>
	</tr>
	<tr>
		<td>
			<ul style="list-style:none;margin:0px;padding:0px;">
				<li><a href="{devblocks_url}c=account&a=email{/devblocks_url}">Email Addresses</a></li>
				{if !empty($login_extensions_enabled) && isset($login_extensions_enabled.{'sc.login.auth.openid'})}
					<li><a href="{devblocks_url}c=account&a=openid{/devblocks_url}">OpenID Identities</a></li>
				{/if}
				
				{if !empty($login_extensions_enabled) && isset($login_extensions_enabled.{'sc.login.auth.default'})}
					<li><a href="{devblocks_url}c=account&a=password{/devblocks_url}">Change Password</a></li>
				{/if}
				
				<li><a href="{devblocks_url}c=account&a=sharing{/devblocks_url}">Sharing/Collaboration</a></li>
				<li><a href="{devblocks_url}c=account&a=delete{/devblocks_url}">Delete My Account</a></li>
			</ul>
		</td>
	</tr>
</table>
<br>
