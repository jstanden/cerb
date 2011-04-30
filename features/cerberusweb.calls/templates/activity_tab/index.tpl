<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=calls&a=showEntry&id=0&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.calls&f=images/phone_call.png{/devblocks_url}" align="top"> {$translate->_('calls.ui.log_call')|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	// Don't trigger on forms
	if($(event.target).is(':input'))
		return;

	// [TODO] Shared
	
	switch(event.which) {
		case 98:  // (B) bulk update
			try {
				$('#btnactivity_callsBulkUpdate').click();
			} catch(e) { } 
			break;
		case 101:  // (E) explore
			try {
				$('#btnExploreactivity_calls').click();
			} catch(e) { } 
			break;
	}	
});
{/if}
</script>