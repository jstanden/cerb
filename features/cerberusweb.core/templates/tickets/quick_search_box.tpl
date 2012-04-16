{if $active_worker->hasPriv('core.mail.search')}
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doQuickSearch">
<span id="tourHeaderQuickLookup"><b>{$translate->_('mail.nav.search_mail')}</b></span> <select name="type">
	<option value="sender"{if $quick_search_type eq 'sender'}selected{/if}>{$translate->_('ticket.first_wrote')|capitalize}</option>
	<option value="requester"{if $quick_search_type eq 'requester'}selected{/if}>{$translate->_('ticket.requester')|capitalize}</option>
	<option value="mask"{if $quick_search_type eq 'mask'}selected{/if}>{$translate->_('ticket.id')}</option>
	<option value="org"{if $quick_search_type eq 'org'}selected{/if}>{$translate->_('contact_org.name')|capitalize}</option>
	<option value="subject"{if $quick_search_type eq 'subject'}selected{/if}>{$translate->_('ticket.subject')|capitalize}</option>
	<option value="comments_all"{if $quick_search_type eq 'comments_all'}selected{/if}>{$translate->_('mail.quick_search.comments_all')}</option>
	<option value="comments_phrase"{if $quick_search_type eq 'comments_phrase'}selected{/if}>{$translate->_('mail.quick_search.comments_phrase')}</option>
	<option value="comments_expert"{if $quick_search_type eq 'comments_expert'}selected{/if}>{$translate->_('mail.quick_search.comments_expert')}</option>
	<option value="messages_all"{if $quick_search_type eq 'messages_all'}selected{/if}>{$translate->_('mail.quick_search.messages_all')}</option>
	<option value="messages_phrase"{if $quick_search_type eq 'messages_phrase'}selected{/if}>{$translate->_('mail.quick_search.messages_phrase')}</option>
	<option value="messages_expert"{if $quick_search_type eq 'messages_expert'}selected{/if}>{$translate->_('mail.quick_search.messages_expert')}</option>
</select><input type="text" name="query" class="input_search" size="16" class="input_search"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
{/if}