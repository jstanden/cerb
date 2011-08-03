<div style="float:right;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="datacenter.domains">
<input type="hidden" name="a" value="doQuickSearch">
<b>{$translate->_('common.quick_search')}</b> <select name="type">
	<option value="name"{if $quick_search_type eq 'name'}selected{/if}>{$translate->_('common.name')|capitalize}</option>
	<option value="comments_all"{if $quick_search_type eq 'comments_all'}selected{/if}>{$translate->_('mail.quick_search.comments_all')}</option>
	<option value="comments_phrase"{if $quick_search_type eq 'comments_phrase'}selected{/if}>{$translate->_('mail.quick_search.comments_phrase')}</option>
</select><input type="text" name="query" class="input_search" size="16"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
</div>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
{if 1 || $active_worker->hasPriv('xxx')}<button type="button" onclick="genericAjaxPopup('peek','c=datacenter.domains&a=showDomainPeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {$translate->_('cerberusweb.datacenter.domains.tab.add')}</button>{/if}
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}
