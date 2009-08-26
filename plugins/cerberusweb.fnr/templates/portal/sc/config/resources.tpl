<div style="margin-left:10px;">
{$translate->_('fnr.portal.sc.cfg.topics')}<br>
<br>

{foreach from=$fnr_topics item=topic key=topic_id}
	<label><input type="checkbox" name="topic_ids[]" value="{$topic_id}" {if isset($enabled_topics.$topic_id)}checked="checked"{/if}> <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top"> {$topic->name}</label><br>
{/foreach}
</div>
<br>

