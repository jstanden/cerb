<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWidgetAdd">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="dashboards">
<input type="hidden" name="action" value="addWidgetPopupJson">
<input type="hidden" name="workspace_tab_id" value="{$workspace_tab_id}">

<fieldset class="peek" style="margin-bottom:0px;border-top:0;">
	<b>Type:</b>
	<select name="extension_id">
		{foreach from=$widget_extensions item=widget_extension}
		<option value="{$widget_extension->id}">{$widget_extension->name}</option>
		{/foreach}
	</select>
</fieldset>

<div style="margin-top:10px;">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
$popup = genericAjaxPopupFind('#frmWidgetAdd');
$popup.one('popup_open', function(event,ui) {
	$(this).dialog('option','title',"{'Add Widget'}");
	
	var $frm = $(this).find('form');
	
	$frm.find('button.submit').click(function(e) {
		genericAjaxPost('frmWidgetAdd','',null,function(json) {
			$popup = genericAjaxPopupFind('#frmWidgetAdd');

			if(null == json)
				return;
			
			if(null == json.widget_id)
				return;
			
			$event = new jQuery.Event('new_widget');
			$event.widget_id = json.widget_id;
			
			// Send the result to the caller
			$popup.trigger($event);
			
			// Close the popup
			//$popup.dialog('close');
		});
	});
});
</script>
