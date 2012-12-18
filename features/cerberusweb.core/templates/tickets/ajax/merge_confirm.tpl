<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewMergeTickets">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset>
	<legend>Are you sure you want to merge these tickets?</legend>
	
	The selected tickets will be merged into a single ticket. 
	Their conversation history, recipients, links, and comments will be combined. 
	<b>This action cannot be undone.</b>
	
	<div style="padding:5px;">
		<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'mail.merge'|devblocks_translate|capitalize}</button>
		<button type="button" class="cancel"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('merge');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'mail.merge'|devblocks_translate|capitalize}");
		
		$this.find('button.submit').click(function() {
			ajax.viewTicketsAction('{$view_id}','merge');
			genericAjaxPopupClose('merge');
		});
		
		$this.find('button.cancel').click(function() {
			genericAjaxPopupClose('merge');
		});
	});
</script>