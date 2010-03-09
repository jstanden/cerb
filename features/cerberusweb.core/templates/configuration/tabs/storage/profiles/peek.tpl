{if !empty($profile->id)}
<div class="ui-state-error ui-corner-all" style="margin: 0 0 .5em 0; padding: 0 .7em;"> 
	<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
	[[WARNING ABOUT CHANGING PARAMS ON A LIVE STORAGE PROFILE]]
	</p>
</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formStorageProfilePeek" name="formStorageProfilePeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveStorageProfilePeek">
<input type="hidden" name="id" value="{$profile->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><b>{$translate->_('Name')|capitalize}:</b> </td>
		<td width="100%"><input type="text" name="name" value="{$profile->name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('Engine')|capitalize}:</b> </td>
		<td width="100%">
			{if empty($profile->id)}
			<select name="extension_id" onchange="genericAjaxGet('divStorageEngineSettings','c=config&a=showStorageProfileConfig&ext_id='+escape(selectValue(this))+'&id='+escape(this.form.id.value));">
				{foreach from=$engines item=engine_mft key=engine_id}
				<option value="{$engine_id}" {if $profile->extension_id==$engine_id}selected="selected"{/if}>{$engine_mft->name}</option>
				{/foreach}
			</select>
			{else}
				{$profile_extid = $profile->extension_id}
				{if isset($engines.$profile_extid)}
					{$engines.$profile_extid->name} ({$profile->extension_id})
				{else}
					{$profile->extension_id}
				{/if}
				<input type="hidden" name="extension_id" value="{$profile->extension_id|escape}">
			{/if}
			<br>
			
			<blockquote id="divStorageEngineSettings" style="margin:5px;background-color:rgb(255,255,255);padding:5px;border:1px dotted rgb(120,120,120);display:{if 1}block{else}none{/if};">
				{if !empty($storage_engine) && is_a($storage_engine,'Extension_DevblocksStorageEngine')}
					{$storage_engine->renderConfig($profile)}
				{/if}
			</blockquote>
		</td>
	</tr>
</table>

<div id="divTestStorageProfile"></div>

{if $active_worker->is_superuser}
	<button type="button" onclick="genericAjaxPanelPostCloseReloadView('formStorageProfilePeek', '{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	{if 0&&$active_worker->is_superuser}<button type="button" onclick="if(confirm('Are you sure you want to delete this worker and their history?')) { this.form.do_delete.value='1';genericAjaxPanelPostCloseReloadView('formStorageProfilePeek', '{$view_id}'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
{/if}
<button type="button" onclick="this.form.a.value = 'testStorageProfilePeek';genericAjaxPost('formStorageProfilePeek','divTestStorageProfile');this.form.a.value = 'saveStorageProfilePeek';"><span class="cerb-sprite sprite-gear"></span> {$translate->_('Test')|capitalize}</button>
<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"{$schema->manifest->name|escape}");
	} );
</script>

