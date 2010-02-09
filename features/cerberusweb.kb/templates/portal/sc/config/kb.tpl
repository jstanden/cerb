<div style="margin-left:10px;">
{$translate->_('portal.sc.cfg.choose_kb_topics')}<br>
<br>

{assign var=root_id value="0"}
{foreach from=$tree_map.$root_id item=category key=category_id}
	<label><input type="checkbox" name="category_ids[]" value="{$category_id}" {if isset($kb_roots.$category_id)}checked="checked"{/if}> <span class="cerb-sprite sprite-folder"></span> {$categories.$category_id->name}</label><br>
{/foreach}
</div>
<br>
