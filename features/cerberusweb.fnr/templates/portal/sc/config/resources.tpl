<div style="margin-left:10px;">
{$translate->_('fnr.portal.sc.cfg.topics')}<br>
<br>

{foreach from=$fnr_topics item=topic key=topic_id}
	<label><input type="checkbox" name="topic_ids[]" value="{$topic_id}" {if isset($enabled_topics.$topic_id)}checked="checked"{/if}> <span class="cerb-sprite sprite-folder"></span> {$topic->name}</label><br>
{/foreach}
</div>
<br>

