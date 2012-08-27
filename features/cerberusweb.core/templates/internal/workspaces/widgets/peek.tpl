<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWidgetEdit">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="dashboards">
<input type="hidden" name="action" value="saveWidgetPopup">
{if !empty($widget) && !empty($widget->id)}<input type="hidden" name="id" value="{$widget->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek" style="margin-bottom:0px;border-top:0;">
	{if $extension instanceof Extension_WorkspaceWidget}
	<b>Type:</b>
	{$extension->manifest->name} 
	<br>
	{/if}
	
	<b>Label:</b>
	<input type="text" name="label" value="{$widget->label}" size="45">
	<br>
</fieldset>

{* The rest of config comes from the widget *}
{if $extension instanceof Extension_WorkspaceWidget}
{$extension->renderConfig($widget)}
{/if}

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this widget?</legend>
	<button type="button" class="red delete">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div style="margin-top:10px;" class="toolbar">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($widget) && !empty($widget->id)}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$popup = genericAjaxPopupFind('#frmWidgetEdit');
$popup.one('popup_open', function(event,ui) {
	$(this).dialog('option','title',"{'Widget'}");
	
	var $frm = $(this).find('form');
	
	$frm.find('button.delete').click(function(e) {
		$frm = $(this).closest('form');
		$frm.find('input:hidden[name=do_delete]').val('1');
		
		genericAjaxPost('frmWidgetEdit','',null,function(out) {
			$popup = genericAjaxPopupFind('#frmWidgetEdit');
			widget_id = $popup.find('form input:hidden[name=id]').val();

			// Nuke the widget DOM
			$('#widget' + widget_id).remove();
			
			// Close the popup
			$popup.dialog('close');
		});
	});
	
	$frm.find('button.submit').click(function(e) {
		genericAjaxPost('frmWidgetEdit','',null,function(out) {
			$popup = genericAjaxPopupFind('#frmWidgetEdit');
			widget_id = $popup.find('form input:hidden[name=id]').val();
			// Reload the widget
			genericAjaxGet('widget' + widget_id,'c=internal&a=handleSectionAction&section=dashboards&action=renderWidget&widget_id=' + widget_id);
			// Close the popup
			$popup.dialog('close');
		});
	});
});
</script>
