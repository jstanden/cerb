<script type="text/javascript">
$(function() {
	var $subpage = $('BODY > DIV.cerb-subpage');
	var $toolbar = $subpage.find('form.toolbar');
	
	var $new_button = $('<button type="button"/>')
		.attr('title','{'cerb.legacy.profile.attachments.download_all'|devblocks_translate}')
		.append($('<span class="glyphicons glyphicons-paperclip"/>'))
		;
		
	$new_button.click(function(e) {
		document.location.href = '{devblocks_url}c=attachments.zip&a=ticket&id={$page_context_id}{/devblocks_url}';
	});
	
	$new_button.appendTo($toolbar);
});
</script>