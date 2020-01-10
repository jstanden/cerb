{if $prompts}
<form id="tabFilters{$tab->id}" action="{devblocks_url}{/devblocks_url}" method="POST" onsubmit="return false;" style="padding:5px 10px;display:inline-block;">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="workspace_tab">
	<input type="hidden" name="action" value="saveDashboardTabPrefs">
	<input type="hidden" name="tab_id" value="{$tab->id}">
	
	<div style="margin:5px 10px 0 0;">
		{foreach from=$prompts item=prompt}
			{if $prompt.type == 'chooser'}
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_chooser.tpl" prompt=$prompt}
			{elseif $prompt.type == 'date_range'}
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_date_range.tpl" prompt=$prompt}
			{elseif $prompt.type == 'picklist' && !$prompt.params.multiple}
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_picklist_single.tpl" prompt=$prompt}
			{elseif $prompt.type == 'picklist' && $prompt.params.multiple}
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_picklist_multiple.tpl" prompt=$prompt}
			{elseif $prompt.type == 'text'}
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompt_text.tpl" prompt=$prompt}
			{/if}
		{/foreach}
		
		<div style="display:inline-block;vertical-align:middle;">
			<button type="button" class="cerb-filter-editor--save"><span class="glyphicons glyphicons-refresh"></span> {'common.update'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</form>
{/if}

<script type="text/javascript">
$(function() {
	var $frm = $('#tabFilters{$tab->id}');
	
	$frm.find('.cerb-filter-editor--save')
		.on('click', function(e) {
			var $this = $(this);
			
			genericAjaxPost($frm, '', '', function(json) {
				// Reload the entire dashboard
				var $container = $('#workspaceTab{$tab->id}');
				$container.triggerHandler('cerb-dashboard-refresh');
			});
		})
	;
})
</script>