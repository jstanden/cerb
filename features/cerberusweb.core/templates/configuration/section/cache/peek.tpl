<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCachePeek" name="frmCachePeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="cache">
<input type="hidden" name="action" value="saveCachePeek">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{foreach from=$engines item=engine key=engine_id}
<fieldset class="peek" style="margin-bottom:0;">
	<legend><label><input type="radio" name="engine_extension_id" value="{$engine->id}" {if $current_cacher->id == $engine_id}checked="checked"{/if}> {$engine->manifest->name}</label></legend>
	
	{if $current_cacher->id == $engine_id}
	<div>
		{$current_cacher->renderConfig()}
	</div>
	{else}
	<div style="display:none;">
		{$engine->renderConfig()}
	</div>
	{/if}
</fieldset>
{/foreach}

<div style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Cache Configuration");
		
		var $frm = $('#frmCachePeek');
		var $fieldsets = $frm.find('> fieldset');
		
		$frm.find('fieldset legend input:radio').on('click', function() {
			$fieldsets.find('> div').hide();
			$(this).closest('fieldset').find('> div').fadeIn();
		});
		
		$frm.find('textarea').autosize();
		
		$frm.find('button.submit').on('click', function() {
			Devblocks.saveAjaxForm($frm,{
				success: function() {
					window.location.href = '{devblocks_url}c=config&a=cache{/devblocks_url}';
				}
			});
		});
	});
});
</script>
