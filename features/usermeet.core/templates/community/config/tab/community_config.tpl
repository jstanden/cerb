<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="formConfigCommunity">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="usermeet.config.tab.communities">
<input type="hidden" name="action" value="saveCommunity">
<input type="hidden" name="id" value="{$community->id}">
<input type="hidden" name="do_delete" value="0">

{if !empty($community->id)}
	<H2>{$community->name}</H2>
{else}
	<H2>{$translate->_('usermeet.ui.community.cfg.add_community')}</H2>
{/if}

<b>{$translate->_('usermeet.ui.community.cfg.community_name')}</b> {$translate->_('usermeet.ui.community.cfg.community_name_hint')}<br>
<input type="text" name="name" size="45" value="{$community->name}"><br>
<br>

<b>{$translate->_('usermeet.ui.community.cfg.add_tool')}</b><br>
 <select name="add_tool_id">
 	<option value="">&nbsp;</option>
	{foreach from=$tool_manifests item=tool}
	<option value="{$tool->id}">{$tool->name}</option>
	{/foreach}
</select>
<br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($community->id)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this community?')){this.form.do_delete.value='1';this.form.submit();}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}

</form>
</div>
