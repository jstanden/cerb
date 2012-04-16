<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="doQuickSearch">
<b>{$translate->_('common.quick_search')}</b> <select name="type">
	<option value="email"{if $quick_search_type eq 'email'}selected{/if}>{$translate->_('crm.opportunity.email_address')|capitalize}</option>
	<option value="org"{if $quick_search_type eq 'org'}selected{/if}>{$translate->_('crm.opportunity.org_name')|capitalize}</option>
	<option value="title"{if $quick_search_type eq 'title'}selected{/if}>{$translate->_('crm.opportunity.name')|capitalize}</option>
	<option value="comments_all"{if $quick_search_type eq 'comments_all'}selected{/if}>{$translate->_('mail.quick_search.comments_all')}</option>
	<option value="comments_phrase"{if $quick_search_type eq 'comments_phrase'}selected{/if}>{$translate->_('mail.quick_search.comments_phrase')}</option>
	<option value="comments_expert"{if $quick_search_type eq 'comments_expert'}selected{/if}>{$translate->_('mail.quick_search.comments_expert')}</option>
</select><input type="text" name="query" class="input_search" size="16"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
