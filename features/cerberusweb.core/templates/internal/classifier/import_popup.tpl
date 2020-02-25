{$popup_id = uniqid()}

<form action="javascript:;" method="POST" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier">
<input type="hidden" name="action" value="saveImportPopupJson">
<input type="hidden" name="classifier_id" value="{$classifier_id}">

<fieldset id="{$popup_id}" class="peek">
	<legend>Enter tagged examples for training:</legend>
	<textarea name="examples_csv" rows="15" cols="50" style="width:100%;" placeholder="classification,expression" autocomplete="off" spellcheck="false"></textarea>
	
	<div style="margin-top:5px;">
		<span><tt>&lt;classification&gt;,&lt;expression&gt;</tt> e.g.:</span>
		<pre style="margin-top:0px;margin-left:20px;">{literal}
yes,ok
yes,go ahead
no,cancel
no,don't do it
reminder,Remind me about {{remind:meeting}} {{time:at 2pm}}
		{/literal}</pre>
	</div>
	
	<div class="status"></div>
	
	<button class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $div = $('#{$popup_id}');
	var $popup = genericAjaxPopupFind($div);
	var $frm = $popup.find('form');
	var $status = $popup.find('div.status');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "Import Classifier Training Data");
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost($frm, '', null, function(json) {
				if(json && json.status) {
					genericAjaxPopupClose($popup);
					
				} else {
					var error = json.error || "An unexpected error occurred.";
					Devblocks.showError($status, error);
				}
			});
		});
	});
});
</script>