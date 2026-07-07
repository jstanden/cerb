{if $prompts}
<form id="tabFilters{$tab->id}" action="{devblocks_url}{/devblocks_url}" method="POST" style="flex:1 1 auto;min-width:0;">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="workspace_tab">
	<input type="hidden" name="action" value="saveDashboardTabPrefs">
	<input type="hidden" name="tab_id" value="{$tab->id}">
	
	<div class="cerb-ui-panel">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--row">
				{include file="devblocks:cerberusweb.core::internal/dashboards/prompts/prompts.tpl" prompts=$prompts}

				<div class="cerb-ui-form--field" style="flex:0 1 auto;">
					<label class="cerb-ui-form--label" aria-hidden="true">&nbsp;</label>
					<div class="cerb-ui-toolbar-strip">
						<button type="button" class="cerb-filter-editor--save cerb-ui-toolbar-button"><span class="cerb-icons cerb-icon-refresh"></span> {'common.update'|devblocks_translate|capitalize}</button>
						<button type="button" class="cerb-filter-editor--reset cerb-ui-toolbar-button"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.reset'|devblocks_translate|capitalize}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#tabFilters{$tab->id}');
	Devblocks.formDisableSubmit($frm);
	
	$frm.find('.cerb-filter-editor--save')
		.on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm, '', '', function() {
				// Reload widgets
				let $container = $('#workspaceTab{$tab->id}');
				$container.triggerHandler('cerb-widgets-refresh');
			});
		})
	;
	
	$frm.find('.cerb-filter-editor--reset')
		.on('click', function(e) {
			e.stopPropagation();

			let formData = new FormData($frm[0]);
			formData.set('reset', '1');
			
			// Reset the dashboard prefs
			genericAjaxPost(formData, '', '', function() {
				// Reload the tab this dashboard lives in
				window.CerbUI?.Tabs?.fromPanel(document.getElementById('workspaceTab{$tab->id}'))?.refresh();
			});
		})
	;
})
</script>