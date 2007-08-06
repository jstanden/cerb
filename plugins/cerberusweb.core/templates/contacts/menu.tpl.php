<div style="position: relative; width:100%; height: 30;">
	<span style="position: absolute; left: 0;"><h1 style="display:inline;">Contacts</h1>
		[ <a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">orgs</a> ]
		[ <a href="{devblocks_url}c=contacts&a=people{/devblocks_url}">people</a> ]
		[ <a href="{devblocks_url}c=contacts&a=import{/devblocks_url}">import</a> ]
		<br>
	</span>
	<span style="position: absolute; right: 0;">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="c_name"{if $quick_search_type eq 'c_name'}selected{/if}>Org Name</option>
			<option value="c_account_number"{if $quick_search_type eq 'c_account_number'}selected{/if}>Acct #</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</span>
</div>
