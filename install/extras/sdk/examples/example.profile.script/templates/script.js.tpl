<script type="text/javascript">
	$subpage = $('BODY > DIV.cerb-subpage');
	$toolbar = $subpage.find('form.toolbar');
	
	$new_button = $('<button type="button"><span class="glyphicons glyphicons-cogwheel"></span> Example Button</button>');
	$new_button.click(function(e) {
		alert("You clicked the button!");
	});
	
	$new_button.appendTo($toolbar);
</script>