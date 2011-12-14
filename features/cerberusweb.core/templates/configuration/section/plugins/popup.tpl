<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCerbPluginPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="plugins">
<input type="hidden" name="action" value="savePopup">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if $is_uninstallable}<input type="hidden" name="uninstall" value="0">{/if}

{*
{if !empty($requirements)}
<ul style="margin-top:0px;color:rgb(200,0,0);">
{foreach from=$requirements item=req}
	<li>{$req}</li>
{/foreach}
</ul>
{/if}
*}

<div>
	<b>{'common.status'|devblocks_translate|capitalize}:</b> 
	<label><input type="radio" name="enabled" value="1" {if $plugin->enabled}checked="checked"{/if}>{'common.enabled'|devblocks_translate|capitalize}</label> 
	<label><input type="radio" name="enabled" value="0" {if !$plugin->enabled}checked="checked"{/if}>{'common.disabled'|devblocks_translate|capitalize}</label> 
</div>

<div style="display:none;" id="divCerb5PluginOutput">
</div>

<fieldset class="delete" style="display:none;padding:10px;" id="fsCerb5PluginUninstall">
	<legend>Are you sure you want to uninstall this plugin?</legend>
	
	<button type="button" class="red" onclick="$(this).closest('form').find('input:hidden[name=uninstall]').val('1');$('#btnPluginSave').click();">Yes, uninstall it</button>
	<button type="button" onclick="$(this).closest('fieldset').hide();$('#divCerb5PluginPopupToolbar').fadeIn();">{'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>

<div style="margin-top:10px;" id="divCerb5PluginPopupToolbar">
	<button type="button" id="btnPluginSave"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $is_uninstallable}<button type="button" onclick="$('#divCerb5PluginPopupToolbar').fadeOut();$(this).closest('form').find('#fsCerb5PluginUninstall').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> Uninstall</button>{/if}
</div>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Plugin: {$plugin->name}');
		
		$frm = $('#frmCerbPluginPeek');
		
		$('#btnPluginSave').click(function() {
			$('#divCerb5PluginPopupToolbar').fadeOut();
			genericAjaxPost('frmCerbPluginPeek','','',function(json) {
				console.log(json);
				
				$('#divCerb5PluginOutput').show();
				
				// [TODO] Errors, or success?
				
				genericAjaxPopupClose('peek');
				// Reload view
	 			{if !empty($view_id)}
				genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id}');
				{/if}
			});
		});
	} );
</script>
