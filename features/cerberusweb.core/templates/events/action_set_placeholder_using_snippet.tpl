<b>With this record:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]">
	{if !empty($values_to_contexts)}
	{foreach from=$values_to_contexts item=context_data key=val_key}
	{if !$context_data.is_multiple}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/if}
	{/foreach}
	{/if}
</select>
</div>

<b>Load this snippet:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<div>
		<button type="button" class="chooser-snippet"><span class="cerb-sprite sprite-view"></span></button>
	</div>
	<div class="snippet-preview">
		{if $snippet && $snippet->id}
		{include file="devblocks:cerberusweb.core::events/action_set_placeholder_using_snippet_params.tpl"}
		{/if}
	</div>
</div>

<b>Save the output to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script type="text/javascript">
$(function(e) {
	var $action = $('fieldset#{$namePrefix}');
	var $on = $action.find('select:first');
	var $snippet_preview = $action.find('div.snippet-preview');
	
	$action.find('textarea').autosize();
	
	$action.find('button.chooser-snippet').on('click', function(e) {
		var context = $action.find('select:first option:selected').attr('context');
		
		var contexts = [];
		contexts.push(context + ':' + 0);
		
		$chooser=genericAjaxPopup('snippet_chooser','c=internal&a=chooserOpenSnippet&single=1&context=cerberusweb.contexts.snippet&contexts=' + contexts.join(','), null, false, '600');
		$chooser.bind('snippet_select', function(event) {
			event.stopPropagation();
			genericAjaxGet($snippet_preview,'c=internal&a=showSnippetPlaceholders&name_prefix={$namePrefix}&id=' + event.snippet_id);
		});
	});
	
});
</script>
