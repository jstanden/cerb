{$popup_id = "popup{uniqid()}"}
<form id="{$popup_id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="ticket">
<input type="hidden" name="action" value="saveResendMessagePopupJson">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<textarea style="height:500px;width:100%;margin-bottom:10px;" readonly="readonly" wrap="off">
{$source}
</textarea>

<div class="status"></div>

<button type="button" class="close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.send'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$popup_id}');
	var $popup = genericAjaxPopupFind($frm);
	var $layer = $popup.attr('data-layer');
	var $status = $popup.find('div.status');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', '{'Send this message again'|devblocks_translate|escape:'javascript' nofilter}');
		
		// Verify send with Ajax, or report error
		
		$popup.find('button.close').click(function() {
			genericAjaxPost($frm, '', null, function(json) {
				if(typeof json == 'object') {
					if(json.status == true) {
						genericAjaxPopupClose($popup);
					} else if (json.status == false && json.error) {
						Devblocks.showError($status, json.error);
					}
				}
			});
		});
	});
});
</script>