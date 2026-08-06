{$form_id = uniqid()}
<form action="#" method="post" id="{$form_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Widget:</b>

{$widget->label}

<div style="clear:both;" id="widgetExportTabs_{$form_id}">
	<ul>
		{if $export_data.json}<li><a href="#widgetExportTabJson_{$form_id}">JSON</a></li>{/if}
		{if $export_data.csv}<li><a href="#widgetExportTabCsv_{$form_id}">CSV</a></li>{/if}
	</ul>
	
	{if $export_data.json}
	<div id="widgetExportTabJson_{$form_id}">
		<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$export_data.json}</textarea>
	</div>
	{/if}
	
	{if $export_data.csv}
	<div id="widgetExportTabCsv_{$form_id}">
		<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$export_data.csv}</textarea>
	</div>
	{/if}
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		let $this = $(this);

		$this.dialog('option','title','Export Widget Data');

		$this.find('#widgetExportTabs_{$form_id} > ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });

		$frm.find('button.submit').click(function() {
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>
