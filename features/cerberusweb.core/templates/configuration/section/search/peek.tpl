<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmSearchSchemaPeek" name="frmSearchSchemaPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="search">
<input type="hidden" name="action" value="saveSearchSchemaPeek">
<input type="hidden" name="schema_extension_id" value="{$schema->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{foreach from=$search_engines item=engine key=engine_id}
<fieldset class="peek">
	<legend><label><input type="radio" name="engine_extension_id" value="{$engine->id}" {if $schema_engine->id==$engine_id}checked="checked"{/if}> {$engine->manifest->name}</label></legend>
	
	{if $engine}
	<div {if $schema_engine->id != $engine_id}style="display:none;"{/if}>
		{$engine->renderConfigForSchema($schema)}
	</div>
	{/if}
</fieldset>
{/foreach}

<div style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$schema->manifest->name|escape:'javascript' nofilter}");
		
		var $frm = $('#frmSearchSchemaPeek');
		var $fieldsets = $frm.find('> fieldset');
		
		$frm.find('fieldset legend input:radio').on('click', function() {
			$fieldsets.find('> div').hide();
			$(this).closest('fieldset').find('> div').fadeIn();
		});
		
		$frm.find('textarea').autosize();
		
		$frm.find('button.submit').on('click', function() {
			Devblocks.saveAjaxForm($frm, {
				success: function() {
					window.location.href = '{devblocks_url}c=config&a=search{/devblocks_url}';
				}
			});
		});
	});
});
</script>