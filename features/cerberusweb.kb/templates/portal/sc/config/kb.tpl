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
<br>

{$uniq_id = uniqid()}

<div id="{$uniq_id}" style="margin-left:10px;">
	<div>
		<b>Worklist columns:</b> (leave blank for default)
	</div>
	
	{foreach from=$kb_params.columns item=selected_token}
	<div style="margin:3px;" class="column">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
	
		<select name="kb_columns[]">
		<option value=""></option>
		{foreach from=$kb_columns item=column key=token}
			<option value="{$token}" {if $token==$selected_token}selected="selected"{/if}>{$column->db_label|capitalize}</option>
		{/foreach}
		</select>
		
		<button type="button" onclick="$(this).closest('div').remove();"><span class="cerb-sprite2 sprite-minus-circle"></span></button>
	</div>
	{/foreach}
	
	<div style="margin:3px;display:none;" class="column template">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
	
		<select name="kb_columns[]">
		<option value=""></option>
		{foreach from=$kb_columns item=column key=token}
			<option value="{$token}">{$column->db_label|capitalize}</option>
		{/foreach}
		</select>
		
		<button type="button" onclick="$(this).closest('div').remove();"><span class="cerb-sprite2 sprite-minus-circle"></span></button>
	</div>
	
	<button type="button" class="add-column"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
	
</div>

<script type="text/javascript">
$(function(e) {
	var $container = $('#{$uniq_id}');
		
	$container
		.sortable({
			items: 'DIV.column',
			handle: 'span.ui-icon-arrowthick-2-n-s',
			placeholder:'ui-state-highlight'
		})
		;
	
	$container
		.find('button.add-column')
		.click(function(e) {
			var $template = $container.find('div.template');
			$template.clone().removeClass('template').insertBefore($template).focus().fadeIn();
		})
		;
});
</script>