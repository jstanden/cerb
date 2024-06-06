<form action="#" method="post" id="frmBehaviorExport">

<b>Behavior:</b>

{$trigger->title}

<div>
	<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$behavior_json}</textarea>
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#frmBehaviorExport');
	Devblocks.formDisableSubmit($popup);
	
	$popup.one('popup_open', function() {
		let $this = $(this);
		
		$this.dialog('option','title','Export Behavior');
		
		$popup.find('button.submit').click(function(e) {
			e.stopPropagation();
			$popup.dialog('close');
		});
	});
});
</script>
