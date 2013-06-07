<form action="javascript:;" method="post" id="frmWorkspacePageExport" onsubmit="return false;">

<div>
	<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$json}</textarea>
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
var $popup = genericAjaxPopupFind('#frmWorkspacePageExport');
$popup.one('popup_open', function(event,ui) {
	var $this = $(this);
	
	$this.dialog('option','title',"{"Export Page: {$page->name|escape:'javascript'}"}");
	
	var $frm = $(this).find('form');
	
	$frm.find('button.submit').click(function(e) {
		var $popup = genericAjaxPopupFind($(this));
		$popup.dialog('close');
	});
});
</script>


