<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCerbPluginPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="plugins">
<input type="hidden" name="action" value="savePopup">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if $is_uninstallable}<input type="hidden" name="uninstall" value="0">{/if}

<div>
	<b>{'common.status'|devblocks_translate|capitalize}:</b> 
	<label><input type="radio" name="enabled" value="1" onclick="$('#pluginConfigTabs').fadeIn();$('#divCerb5PluginOutput').show();" {if $plugin->enabled}checked="checked"{/if}>{'common.enabled'|devblocks_translate|capitalize}</label> 
	<label><input type="radio" name="enabled" value="0" onclick="$('#pluginConfigTabs').fadeOut();$('#divCerb5PluginOutput').hide();" {if !$plugin->enabled}checked="checked"{/if}>{'common.disabled'|devblocks_translate|capitalize}</label> 
</div>

{if !empty($config_exts)}
<div id="pluginConfigTabs" style="margin-top:5px;{if !$plugin->enabled}display:none;{/if}">
	<ul>
	{foreach from=$config_exts item=config_ext}
		{$label = 'Configuration'}
		{if isset($config_ext->manifest->params.tab_label)}
			{$label = $config_ext->manifest->params.tab_label}
		{/if}
		<li><a href="#tab_{$config_ext_id|replace:'.':'_'}">{$label|devblocks_translate|capitalize}</a></li>
	{/foreach}
	</ul>
	
	{foreach from=$config_exts key=config_ext_id item=config_ext}
	<div id="tab_{$config_ext_id|replace:'.':'_'}">
		{if method_exists($config_ext,'render') && is_a($config_ext, 'Extension_PluginSetup')}
		{$config_ext->render()}
		{/if}
	</div>	
	{/foreach}	
</div>
{/if}

<div style="{if empty($requirements)}display:none;{/if}" id="divCerb5PluginOutput">
	<ul style="margin-top:0px;color:rgb(200,0,0);">
	{if !empty($requirements)}
	{foreach from=$requirements item=req}
		<li>{$req}</li>
	{/foreach}
	{/if}
	</ul>
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
		
		{if !empty($config_exts)}
			$('#pluginConfigTabs').tabs();
		{/if}
		
		$frm = $('#frmCerbPluginPeek');
		
		$('#btnPluginSave').click(function() {
			$out = $('#divCerb5PluginOutput');
			$out.find('ul').html('');
			$out.hide();
			
			$('#divCerb5PluginPopupToolbar').fadeOut();
			genericAjaxPost('frmCerbPluginPeek','','',function(json) {
				// Errors? or success
				if(false == json.status) {
					if(null != json.errors)
					for(idx in json.errors) {
						$out.find('ul').append($('<li>' + json.errors[idx] + '</li>'));
					}
					
					$out.fadeIn();
					$('#divCerb5PluginPopupToolbar').fadeIn();
					
				} else {
					genericAjaxPopupClose('peek');
					// Reload view
		 			{if !empty($view_id)}
					genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id}');
					{/if}
					
				}
			});
		});
	} );
</script>
