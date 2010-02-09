<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="fnr.config.tab">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="id" value="{if !empty($fnr_topic->id)}{$fnr_topic->id}{else}0{/if}">
<input type="hidden" name="form_type" value="fnr_topic">
<input type="hidden" name="do_delete" value="0">

<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($fnr_topic->id)}
			<h2>{$translate->_('fnr.ui.cfg.topics.add')|capitalize}</h2>
			{else}
			<h2>{'fnr.ui.cfg.modify'|devblocks_translate:$fnr_topic->name}</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>{$translate->_('common.name')|capitalize}</b></td>
		<td width="100%"><input type="text" name="name" value="{$fnr_topic->name|escape}" size="45"> <i>{$translate->_('fnr.ui.cfg.topics.name.hint')}</i></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($fnr_topic)}<button type="button" onclick="if(confirm('Are you sure you want to delete this topic and all its resources?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>