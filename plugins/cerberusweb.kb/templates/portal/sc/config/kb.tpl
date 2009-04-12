<label><input type="checkbox" name="kb_require_login" value="1" {if $kb_require_login}checked="checked"{/if}> {$translate->_('portal.sc.cfg.kb.require_login')}</label><br>
<br>

<div style="margin-left:10px;">
{$translate->_('portal.sc.cfg.choose_kb_topics')}<br>
<br>

{assign var=root_id value="0"}
{foreach from=$tree_map.$root_id item=category key=category_id}
	<label><input type="checkbox" name="category_ids[]" value="{$category_id}" {if isset($kb_roots.$category_id)}checked="checked"{/if}> <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top"> {$categories.$category_id->name}</label><br>
{/foreach}
</div>
<br>
