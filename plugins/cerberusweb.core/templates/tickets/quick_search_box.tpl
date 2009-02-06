<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doQuickSearch">
<span id="tourHeaderQuickLookup"><b>{$translate->_('mail.nav.search_mail')}</b></span> <select name="type">
	<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>{$translate->_('ticket.first_wrote')|capitalize}</option>
	<option value="requester"{if $quick_search_type eq 'requester'}selected{/if}>{$translate->_('ticket.requester')|capitalize}</option>
	<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>{$translate->_('ticket.id')}</option>
	<option value="org"{if $quick_search_type eq 'org'}selected{/if}>{$translate->_('contact_org.name')|capitalize}</option>
	<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>{$translate->_('ticket.subject')|capitalize}</option>
	<option value="content"{if $quick_search_type eq 'content'}selected{/if}>{$translate->_('message.content')|capitalize}</option>
</select><input type="text" name="query" size="16"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
