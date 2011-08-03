<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="name" {if $quick_search_type eq 'name'}selected{/if}>{$translate->_('contact_org.name')|capitalize}</option>
	<option value="phone" {if $quick_search_type eq 'phone'}selected{/if}>{$translate->_('contact_org.phone')|capitalize}</option>
	<option value="comments_all"{if $quick_search_type eq 'comments_all'}selected{/if}>{$translate->_('mail.quick_search.comments_all')}</option>
	<option value="comments_phrase"{if $quick_search_type eq 'comments_phrase'}selected{/if}>{$translate->_('mail.quick_search.comments_phrase')}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
