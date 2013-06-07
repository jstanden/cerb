<form action="javascript:;" method="post" id="frmWidgetExportData" onsubmit="return false;">

<b>Widget:</b>

{$widget->label}

<div style="clear:both;" id="widgetExportTabs">
	<ul>
		{if $export_data.json}<li><a href="#widgetExportTabJson">JSON</a></li>{/if}
		{if $export_data.csv}<li><a href="#widgetExportTabCsv">CSV</a></li>{/if}
	</ul>
	
	{if $export_data.json}
	<div id="widgetExportTabJson">
		<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$export_data.json}</textarea>
	</div>
	{/if}
	
	{if $export_data.csv}
	<div id="widgetExportTabCsv">
		<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$export_data.csv}</textarea>
	</div>
	{/if}
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
var $popup = genericAjaxPopupFind('#frmWidgetExportData');
$popup.one('popup_open', function(event,ui) {
	var $this = $(this);
	
	$this.dialog('option','title',"{'Export Widget Data'}");
	
	$this.find('#widgetExportTabs').tabs();
	
	var $frm = $(this).find('form');
	
	$frm.find('button.submit').click(function(e) {
		var $popup = genericAjaxPopupFind($(this));
		$popup.dialog('close');
	});
});
</script>
