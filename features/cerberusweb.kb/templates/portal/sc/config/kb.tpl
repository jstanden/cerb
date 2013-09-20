<div style="margin-left:10px;">
	{'portal.sc.cfg.choose_kb_topics'|devblocks_translate}<br>

	<div style="margin-left:10px;">
		{assign var=root_id value="0"}
		{foreach from=$tree_map.$root_id item=category key=category_id}
			<label><input type="checkbox" name="category_ids[]" value="{$category_id}" {if isset($kb_roots.$category_id)}checked="checked"{/if}> <span class="cerb-sprite sprite-folder"></span> {$categories.$category_id->name}</label><br>
		{/foreach}
	</div>
</div>
<br>

<div style="margin-left:10px;">
	By default, display this many knowledgebase articles per page in a list:
	
	<div style="margin-left:10px;">
		{$opts = [5,10,15,20,25,50,100]}
		<select name="kb_view_numrows">
			{foreach from=$opts item=opt}
			<option value="{$opt}" {if $kb_view_numrows==$opt}selected="selected"{/if}>{$opt}</option>
			{/foreach}
		</select>
	</div>
</div>