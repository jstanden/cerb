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
		<button type="button" class="chooser-snippet" data-field-name="{$namePrefix}[snippet_id]" data-context="{CerberusContexts::CONTEXT_SNIPPET}" data-query="" data-query-required="" data-single="true"><span class="glyphicons glyphicons-search"></span></button>
		<ul class="bubbles chooser-container">
			{if $snippet}
			<li>
				<input type="hidden" name="{$namePrefix}[snippet_id]" title="{$snippet->title}" value="{$snippet->id}">
				{$snippet->title}
			</li>
			{/if}
		</ul>
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
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $snippet_preview = $action.find('div.snippet-preview');
	
	// Snippet insert menu
	$action.find('.chooser-snippet')
		.cerbChooserTrigger()
		.on('cerb-chooser-saved', function(e) {
			e.stopPropagation();
			
			var $this = $(this);
			var $ul = $this.siblings('ul.chooser-container');
			
			// Find the snippet_id
			var snippet_id = $ul.find('input:hidden').val();
			
			if(null == snippet_id) {
				$snippet_preview.html('');
				return;
			}
			
			genericAjaxGet('','c=profiles&a=invoke&module=snippet&action=getSnippetPlaceholders&name_prefix={$namePrefix}&id=' + snippet_id, function(html) {
				if(null == html || html.length === 0) {
					$snippet_preview.html('').hide();
					return;
				}
				
				$snippet_preview.html(html).show();
			});
		})
	;
});
</script>
