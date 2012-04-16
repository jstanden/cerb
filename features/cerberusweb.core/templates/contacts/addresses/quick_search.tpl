<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="email">{$translate->_('address.email')|capitalize}</option>
	<option value="org">{$translate->_('contact_org.name')|capitalize}</option>
	<option value="comments_all"{if $quick_search_type eq 'comments_all'}selected{/if}>{$translate->_('mail.quick_search.comments_all')}</option>
	<option value="comments_phrase"{if $quick_search_type eq 'comments_phrase'}selected{/if}>{$translate->_('mail.quick_search.comments_phrase')}</option>
	<option value="comments_expert"{if $quick_search_type eq 'comments_expert'}selected{/if}>{$translate->_('mail.quick_search.comments_expert')}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
