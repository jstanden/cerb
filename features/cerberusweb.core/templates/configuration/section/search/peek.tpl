<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmSearchSchemaPeek" name="frmSearchSchemaPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="search">
<input type="hidden" name="action" value="saveSearchSchemaPeek">
<input type="hidden" name="schema_extension_id" value="{$schema->id}">

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

<div class="status"></div>

<div style="margin-top:10px;">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$schema->manifest->name|escape:'javascript' nofilter}");
		
		var $frm = $('#frmSearchSchemaPeek');
		var $fieldsets = $frm.find('> fieldset');
		var $status = $frm.find('div.status');
		
		$frm.find('fieldset legend input:radio').on('click', function() {
			$fieldsets.find('> div').hide();
			$(this).closest('fieldset').find('> div').fadeIn();
		});
		
		$frm.find('textarea').elastic();
		
		$frm.find('button.submit').on('click', function() {
			$status.html('').hide();
			
			genericAjaxPost($frm, '', null, function(json) {
				if(json && json.error) {
					Devblocks.showError('#frmSearchSchemaPeek div.status', json.error, true);
					
				} else {
					window.location.href = '{devblocks_url}c=config&a=search{/devblocks_url}';
				}
				
			});
		});
	});
});
</script>