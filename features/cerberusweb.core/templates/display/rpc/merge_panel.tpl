<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayMerge" name="frmDisplayMerge">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveMergePanel">
<input type="hidden" name="src_ticket_id" value="{$ticket_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Merge with tickets:</b><br>
<button type="button" class="chooser_ticket"><span class="glyphicons glyphicons-search"></span></button>
<br>
<br>

<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('merge');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{'mail.merge'|devblocks_translate|escape:'javascript' nofilter}');
		$popup.dialog('option', 'resizable', false);
		$popup.dialog('option', 'minHeight', 50);
		
		$('#frmDisplayMerge button.chooser_ticket').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.ticket','dst_ticket_id', { autocomplete: true} );
		});
		
		$popup.find('input:text:first').focus();
	});
});
</script>