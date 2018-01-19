<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewMergeTickets">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Are you sure you want to merge these tickets?</legend>
	
	The selected tickets will be merged into a single ticket. 
	Their conversation history, recipients, links, and comments will be combined. 
	<b>This action cannot be undone.</b>
	
	<div style="padding:5px;">
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.merge'|devblocks_translate|capitalize}</button>
		<button type="button" class="cancel"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('merge');
	
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'common.merge'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$this.find('button.submit')
			.click(function() {
				ajax.viewTicketsAction('{$view_id}','merge');
				genericAjaxPopupClose($popup);
			})
			.focus()
			;
		
		$this.find('button.cancel').click(function() {
			genericAjaxPopupClose($popup);
		});
	});
});
</script>