<form id="frmConfigPlugins" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="savePlugins">

<a href="javascript:;" onclick="checkAll('frmConfigPlugins', true);">select all</a>
 | 
<a href="javascript:;" onclick="checkAll('frmConfigPlugins', false)">select none</a>
 

<ul style="list-style:none;margin-left:0;padding-left:0;text-indent:0;">
{foreach from=$plugins item=plugin}
		<li style='padding-bottom:5px;'>
			<div style="{if $plugin->enabled}border:1px solid rgb(0,120,0);background-color:rgb(255,255,255);{else}margin-left:10px;border:1px solid rgb(180,180,180);background-color:rgb(240,240,240);{/if}" id="config_plugin_{$plugin->id}">
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td nowrap="nowrap" width="0%" valign="middle" rowspan="2" style="background-color:{if $plugin->enabled}rgb(100,200,100){else}rgb(200,200,200){/if};">
						<input type="checkbox" name="plugins_enabled[]" value="{$plugin->id}" {if $plugin->enabled}checked{/if}>
					</td>
					<td width="100%" style="padding-left:5px;" onclick="if(getEventTarget(event)=='TD') checkAll('config_plugin_{$plugin->id}');">
						<span style="{if $plugin->enabled}font-size:120%;font-weight:bold;{else}font-weight:bold;color:rgb(120,120,120);{/if}">{$plugin->name}</span> &nbsp; 
						<!-- (Revision: {$plugin->revision}) -->
						{if !empty($plugin->link)}<a href="{$plugin->link}" target="_blank">more info</a>{/if}
						<br>
						by <span style="font-weight:normal;color:rgb(120,120,120);">{$plugin->author}</span>
					</td>
				</tr>
				<tr>
					<td style="padding-left:5px;">
						{$plugin->description}	
					</td>
				</tr>
			</table>
			</div>
		</li>
{foreachelse}
	<li>No extensions installed.</li>
{/foreach}
</ul>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

