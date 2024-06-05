<form action="javascript:;" method="post" id="frmWidgetExportData">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

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
	<button class="submit" type="button"><span class="glyphicons glyphicons-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmWidgetExportData');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		let $this = $(this);

		$this.dialog('option','title','Export Widget Data');

		$this.find('#widgetExportTabs').tabs();

		$frm.find('button.submit').click(function() {
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>
