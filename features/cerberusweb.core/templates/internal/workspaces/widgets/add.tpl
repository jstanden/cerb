<div id="frmWidgetAddTabs">
	<ul>
		<li><a href="#divWidgetAddBuild">Build</a></li>
		<li><a href="#divWidgetAddImport">Import</a></li>
	</ul>
	
	<div id="divWidgetAddBuild">
		<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWidgetAddBuild">
		<input type="hidden" name="c" value="internal">
		<input type="hidden" name="a" value="handleSectionAction">
		<input type="hidden" name="section" value="dashboards">
		<input type="hidden" name="action" value="addWidgetPopupJson">
		<input type="hidden" name="workspace_tab_id" value="{$workspace_tab_id}">
		
		<b>Type:</b>
		<select name="extension_id">
			{foreach from=$widget_extensions item=widget_extension}
			<option value="{$widget_extension->id}">{$widget_extension->name}</option>
			{/foreach}
		</select>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>
		</div>
		</form>
	</div>
	
	<div id="divWidgetAddImport">
		<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWidgetAddImport">
		<input type="hidden" name="c" value="internal">
		<input type="hidden" name="a" value="handleSectionAction">
		<input type="hidden" name="section" value="dashboards">
		<input type="hidden" name="action" value="addWidgetImportJson">
		<input type="hidden" name="workspace_tab_id" value="{$workspace_tab_id}">

		<div class="import">
			<b>Import:</b> (.json format)
			
			<div>
				<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false"></textarea>
			</div>
		</div>
		
		<div class="configure"></div>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>
		</div>
		</form>
	</div>
</div>

<script type="text/javascript">
$popup = genericAjaxPopupFind('#frmWidgetAddTabs');
$popup.one('popup_open', function(event,ui) {
	$(this).dialog('option','title',"{'Add Widget'}");
	
	$('#frmWidgetAddTabs').tabs();
	
	var $frm = $('#frmWidgetAddBuild');
	
	$frm.find('button.submit').click(function(e) {
		genericAjaxPost('frmWidgetAddBuild','',null,function(json) {
			$popup = genericAjaxPopupFind('#frmWidgetAddBuild');

			if(null == json)
				return;
			
			if(null == json.widget_id)
				return;
			
			$event = new jQuery.Event('new_widget');
			$event.widget_id = json.widget_id;
			
			// Send the result to the caller
			$popup.trigger($event);
		});
	});
	
	var $frm = $('#frmWidgetAddImport');
	
	$frm.find('button.submit').click(function(e) {
		genericAjaxPost('frmWidgetAddImport','',null,function(json) {
			$popup = genericAjaxPopupFind('#frmWidgetAddImport');

			if(null == json)
				return;
			
			if(json.config_html) {
				var $frm = $('#frmWidgetAddImport');
				
				$frm.find('div.import').hide();
				$frm.find('div.configure').hide().html(json.config_html).fadeIn();
				
			} else {
				if(null == json.widget_id)
					return;
				
				$event = new jQuery.Event('new_widget');
				$event.widget_id = json.widget_id;
				
				// Send the result to the caller
				$popup.trigger($event);
			}
			
		});
	});
});
</script>
