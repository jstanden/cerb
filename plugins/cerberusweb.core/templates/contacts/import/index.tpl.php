<div style="position: relative; width:100%; height: 30;">
	<span style="position: absolute; left: 0; top:0;"><h1 style="display:inline;">Import</h1>&nbsp;
		{include file="file:$path/contacts/menu.tpl.php"}
	</span>
	<span style="position: absolute; right: 0; top:0;">
	<!-- 
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>Sender</option>
			<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>Ticket ID</option>
			<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>Subject</option>
			<option value="content"{if $quick_search_type eq 'content'}selected{/if}>Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
		 -->
	</span>
</div>

<div class="block">
<H2>Import Records</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="formContactImport">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="parseUpload">

<b>Record Type:</b><br>
<label><input type="radio" name="type" value="orgs" checked>Organizations</label>
<label><input type="radio" name="type" value="addys">E-mail Addresses</label>
<br>
<br>

<b>Upload .CSV File:</b><br>
<input type="file" name="csv_file" size="45"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button><br>
</form>
</div>
<br>
