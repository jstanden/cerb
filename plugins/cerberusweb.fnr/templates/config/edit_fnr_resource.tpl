<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="fnr.config.tab">
<input type="hidden" name="plugin_id" value="{$plugin->id}">
<input type="hidden" name="id" value="{if !empty($fnr_resource->id)}{$fnr_resource->id}{else}0{/if}">
<input type="hidden" name="form_type" value="fnr_resource">
<input type="hidden" name="do_delete" value="0">

<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($fnr_resource->id)}
			<h2>{$translate->_('fnr.ui.cfg.resources.add')|capitalize}</h2>
			{else}
			<h2>{'fnr.ui.cfg.modify'|devblocks_translate:$fnr_resource->name}</h2>
			{/if}
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>{$translate->_('common.name')|capitalize}:</b></td>
		<td width="100%"><input type="text" name="name" value="{$fnr_resource->name|escape}" size="45"> <i>{$translate->_('fnr.ui.cfg.resources.name.hint')}</i></td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>{$translate->_('fnr.ui.cfg.resources.url')}:</b></td>
		<td width="100%">
			<input type="text" name="url" value="{$fnr_resource->url|escape}" size="64">
			(<a href="http://wiki.cerb4.com/wiki/Fetch_%26_Retrieve_Cookbook" target="_blank">Examples</a>)
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>{$translate->_('fnr.ui.cfg.topic')|capitalize}:</b></td>
		<td width="100%">
			<select name="topic_id">
				{foreach from=$fnr_topics item=fnr_topic}
				<option value="{$fnr_topic->id}" {if $fnr_resource->topic_id==$fnr_topic->id}selected="selected"{/if}>{$fnr_topic->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td colspan="2">
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($fnr_resource)}<button type="button" onclick="if(confirm('Are you sure you want to delete this resource?')){literal}{{/literal}this.form.do_delete.value=1;this.form.submit();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>