<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formPortalTemplatePeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="portals">
<input type="hidden" name="action" value="saveTemplatePeek">
<input type="hidden" name="id" value="{$template->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{$template->path}:</b><br>
<textarea name="content">{$template->content}</textarea><br>
<br>

{if $active_worker->is_superuser}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !$disabled}
		{if $active_worker->is_superuser}<button type="button" class="revert"><span class="glyphicons glyphicons-refresh"></span></a> {'Revert'|devblocks_translate|capitalize}</button>{/if}
	{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formPortalTemplatePeek');
	var $frm = $popup.find('form');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Edit Custom Template");
		
		$popup.find('textarea[name=content]').cerbCodeEditor();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formPortalTemplatePeek', 'view{$view_id}', '', function() {
				genericAjaxPopupClose('peek');
			});
		});
		
		$popup.find('button.revert').click(function() {
			if(confirm('Are you sure you want to revert this template to the default?')) { 
				$frm.find('input[name=do_delete]').val('1');
				
				genericAjaxPost('formPortalTemplatePeek', 'view{$view_id}', '', function() {
					genericAjaxPopupClose('peek');
				});
			}
		});
	});
});
</script>