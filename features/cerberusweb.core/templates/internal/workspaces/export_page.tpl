<form action="javascript:;" method="post" id="frmWorkspacePageExport" onsubmit="return false;">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div>
	<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$json}</textarea>
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
var $popup = genericAjaxPopupFind('#frmWorkspacePageExport');
$popup.one('popup_open', function(event,ui) {
	var $this = $(this);

	var title = "Export Page: " + {$page->name|json_encode nofilter};
	$this.dialog('option','title',title);
	
	var $frm = $(this).find('form');
	
	$frm.find('button.submit').click(function(e) {
		var $popup = genericAjaxPopupFind($(this));
		$popup.dialog('close');
	});
});
</script>


