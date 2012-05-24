<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCerb6PluginDownload">
{*
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="plugin_library">
<input type="hidden" name="action" value="saveDownloadPopup">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="view_id" value="{$view_id}">
*}

<b>{$plugin->name}</b><br>
<br>

{if !empty($requirements)}
<ul style="margin-top:0px;color:rgb(200,0,0);">
{foreach from=$requirements item=req}
	<li>{$req}</li>
{/foreach}
</ul>
{/if}

<div style="display:none;" id="divCerb6PluginOutput">
</div>

<div style="margin-top:10px;">
	{if !empty($requirements)}
	<button type="button" onclick="genericAjaxPopupClose('peek');"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	{else}
	<button type="button" id="btnPluginDownload"><span class="cerb-sprite2 sprite-tick-circle"></span> Download and install</button>
	{/if} 
</div>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Download Plugin');
		
		$frm = $('#frmCerb6PluginDownload');
		
		$('#btnPluginDownload').click(function() {
			$(this).hide();
			genericAjaxGet('', 'c=config&a=handleSectionAction&section=plugin_library&action=saveDownloadPopup&plugin_id={$plugin->id}', function(json) {
				$('#divCerb6PluginOutput').show();
				
				// [TODO] Errors, or success?
				
				genericAjaxPopupClose('peek');
				genericAjaxPopup('peek','c=config&a=handleSectionAction&section=plugins&action=showPopup&plugin_id={$plugin->plugin_id}',null,true,'550');				

				// Reload view
	 			{if !empty($view_id)}
				genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id}');
				{/if}
			});
		});
	} );
</script>
