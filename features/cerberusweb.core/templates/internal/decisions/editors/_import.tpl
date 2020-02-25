<form action="javascript:;" method="post" id="frmBehaviorImport" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="saveImportPopupJson">
<input type="hidden" name="trigger_id" value="{$trigger->id}">
<input type="hidden" name="node_id" value="{$node_id}">

<b>Behavior:</b>

{$trigger->title}

<div>
	<textarea name="behavior_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste the behavior JSON here."></textarea>
</div>

<div class="config"></div>

<div class="status"></div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmBehaviorImport');
	var $popup = genericAjaxPopupFind($frm);
	var $status = $popup.find('div.status');
	var $config = $popup.find('div.config');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Import Behavior Fragment'}");
		
		$frm.find('button.submit').click(function(e) {
			$status.hide().html('');
			
			genericAjaxPost($frm,'','',function(json) {
				if(json && json.config_html) {
					$config.hide().html(json.config_html).fadeIn();
					return;
				}
				
				if(!json || !json.status) {
					Devblocks.showError($status, json.error);
					return;
				}
				
				if(json.status) {
					genericAjaxGet('decisionTree{$trigger->id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger->id}');
					$popup.dialog('close');
					return;
				}
			});
		});
	});
});
</script>
