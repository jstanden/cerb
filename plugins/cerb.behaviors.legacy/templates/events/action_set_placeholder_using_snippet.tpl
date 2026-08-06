<b>With this record:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]">
	{if !empty($values_to_contexts)}
	{foreach from=$values_to_contexts item=context_data key=val_key}
	{if !$context_data.is_multiple}
	{if $context_data.label}<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>{/if}
	{/if}
	{/foreach}
	{/if}
</select>
</div>

<b>Load this snippet:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<div>
		<div class="cerb-ui-record-chooser">
			{if $snippet}
				<li data-context="{CerberusContexts::CONTEXT_SNIPPET}" data-context-id="{$snippet->id}" data-label="{$snippet->title}"></li>
			{/if}
		</div>
	</div>
	<div class="snippet-preview">
		{if $snippet && $snippet->id}
		{include file="devblocks:cerb.behaviors.legacy::events/action_set_placeholder_using_snippet_params.tpl"}
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $action = $('#{$namePrefix}_{$nonce}');
	const $snippet_preview = $action.find('div.snippet-preview');

	if(window.CerbUI && CerbUI.RecordChooser)
		$action.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_SNIPPET}',
				name: '{$namePrefix}[snippet_id]',
				emptyIcon: 'clipboard',
				onSelect: function(item) {
					if(!item.id) {
						$snippet_preview.html('').hide();
						return;
					}
					genericAjaxGet('', 'c=profiles&a=invoke&module=snippet&action=getSnippetPlaceholders&name_prefix={$namePrefix}&id=' + item.id, function(html) {
						if(!html || html.length === 0) {
							$snippet_preview.html('').hide();
							return;
						}
						$snippet_preview.html(html).show();
					});
				}
			});
		});
});
</script>
