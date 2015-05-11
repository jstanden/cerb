{$btnProfileOrgAddPeople = uniqid()}
<form action="javascript:;">
	<input type="hidden" name="c" value="contacts">
	<input type="hidden" name="a" value="saveOrgAddPeoplePopup">
	<input type="hidden" name="org_id" value="{$org_id}">
	<button type="button" id="{$btnProfileOrgAddPeople}"><span class="glyphicons glyphicons-circle-plus"></span> Add People</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"}

<script type="text/javascript">
$(function() {
	var $btn = $('#{$btnProfileOrgAddPeople}');
	var $frm = $btn.closest('form');

	$btn.click(function() {
		var $chooser = genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context={CerberusContexts::CONTEXT_ADDRESS}',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			if(0 == event.values.length)
				return;
			
			for(var idx in event.labels) {
				var $hidden = $('<input type="hidden" name="address_ids[]">');
				$hidden.val(event.values[idx]);
				$hidden.appendTo($frm);
			}
			
			// Send POST to link contacts
			genericAjaxPost($frm, '', null, function() {
				// Refresh worklist
				genericAjaxGet('view{$view->id}', 'c=internal&a=viewRefresh&id={$view->id}');
				
				// Reset the chooser's hidden values in the form
				$frm.find('input:hidden[name="address_ids[]"]').remove();
			});
			
		});
	});
});
</script>
