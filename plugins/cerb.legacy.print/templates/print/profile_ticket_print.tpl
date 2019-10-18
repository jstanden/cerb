<script type="text/javascript">
var $subpage = $('BODY > DIV.cerb-subpage');
var $toolbar = $subpage.find('form.toolbar');

var $new_button = $('<button type="button"/>')
	.attr('title','{'common.print'|devblocks_translate|capitalize}')
	.append($('<span class="glyphicons glyphicons-print"/>'))
	;
	
$new_button.click(function(e) {
	document.location.href = '{devblocks_url}c=print&a=ticket&id={$page_context_id}{/devblocks_url}';
});

$new_button.appendTo($toolbar);
</script>