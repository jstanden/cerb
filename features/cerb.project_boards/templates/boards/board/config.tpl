<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Display project board:</legend>

<div class="cerb-ui-record-chooser" id="boardConfigChooser{$workspace_tab->id}">
	{$board = DAO_ProjectBoard::get($workspace_tab->params.board_id)}
	{if $board}
		<li data-context-id="{$board->id}" data-label="{$board->name}"></li>
	{/if}
</div>

</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {literal}{{/literal}
	if(!(window.CerbUI && CerbUI.RecordChooser)) return;
	let el = document.getElementById('boardConfigChooser{$workspace_tab->id}');
	if(el) new CerbUI.RecordChooser(el, { context: '{CerberusContexts::CONTEXT_PROJECT_BOARD}', name: 'params[board_id]', emptyIcon: 'kanban' });
})();
</script>
