{$tab_uniqid = "{uniqid()}"}
{$tree_dom_id = "decisionTree{$tab_uniqid}"}
{$is_writeable = CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_BEHAVIOR, $behavior, $active_worker)}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
.cerb-behavior-node { cursor:pointer; user-select:none; margin:2px 0; }
.cerb-behavior-node .cerb-ui-tile--caret { margin-left:0.4em; color:var(--cerb-color-background-contrast-160); font-size:0.85em; }
</style>

<form id="{$tree_dom_id}" data-behavior-tree-id="{$behavior->id}" action="#" style="margin-top:10px;">
	<input type="hidden" name="trigger_id[]" value="{$behavior->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/tree.tpl" trigger=$behavior event=$event is_writeable=$is_writeable tree_dom_id=$tree_dom_id}
</form>

<div id="nodeMenu{$tab_uniqid}" hidden></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{if $is_writeable && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_BEHAVIOR}.update")}
$(function() {
	let $frm = $('#{$tree_dom_id}');
	let nodeMenu = null;

	Devblocks.formDisableSubmit($frm);

	// This behavior's tree can be shown in several widgets/cards at once; refresh every instance, telling each
	// one its own container id so the re-rendered tree's drag/drop binds to the right copy.
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$behavior->id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$behavior->id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	// Prepend each menu item's glyph from its data-icon (bare name -> cerb-icon-<name>).
	const renderNodeMenuIcon = function(li, src) {
		const icon = src.dataset.icon;
		if(!icon) return;
		const chip = document.createElement('span');
		chip.className = 'cerb-icons cerb-icon-' + icon;
		chip.style.marginRight = '0.5em';
		li.insertBefore(chip, li.firstChild);
	};

	// Dispatch the chosen menu item to its existing popup/post (ported 1:1 from the legacy menu script).
	const dispatchNodeMenuAction = function(li, src) {
		const node_id = src.dataset.cerbNodeId;
		const node_type = src.dataset.cerbNodeType;

		switch(src.dataset.cerbAction) {
			case 'edit_behavior':
				src.querySelector('.cerb-peek-trigger')?.click();
				break;
			case 'edit_history':
				// Read-only change history. Behaviors persist on every edit, so there's no live "Current" distinct from
				// the latest snapshot — include_current=0 drops it and defaults to comparing the two latest snapshots.
				const fd = new FormData();
				fd.set('c', 'internal');
				fd.set('a', 'invoke');
				fd.set('module', 'records');
				fd.set('action', 'showChangesetsPopup');
				fd.set('record_type', 'behavior');
				fd.set('record_id', '{$behavior->id}');
				fd.set('record_key', 'behavior');
				fd.set('include_current', '0');
				genericAjaxPopup('behaviorChangesets{$behavior->id}', fd, null, null, '80%');
				break;
			case 'simulate_tree':
				genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$behavior->id}',null,false,'50%');
				break;
			case 'export_tree':
				genericAjaxPopup('export_behavior','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$behavior->id}',null,false,'50%');
				break;
			case 'reorder_tree':
				genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&trigger_id={$behavior->id}',null,false,'50%');
				break;
			case 'edit_node':
				genericAjaxPopup('node_' + node_type + node_id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(node_id),null,false,'50%');
				break;
			case 'duplicate_node':
				let formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'behavior');
				formData.set('action', 'duplicateNode');
				formData.set('id', node_id);
				genericAjaxPost(formData,null,null,function() {
					refreshTrees();
				});
				break;
			case 'reorder_node':
				genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&id=' + encodeURIComponent(node_id),null,false,'50%');
				break;
			case 'decision_add':
				genericAjaxPopup('node_switch','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}&type=switch',null,false,'75%');
				break;
			case 'outcome_add':
				genericAjaxPopup('node_outcome','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}&type=outcome',null,false,'75%');
				break;
			case 'action_add':
				genericAjaxPopup('node_action','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}&type=action',null,false,'75%');
				break;
			case 'loop_add':
				genericAjaxPopup('node_loop','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}&type=loop',null,false,'75%');
				break;
			case 'subroutine_add':
				genericAjaxPopup('node_subroutine','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}&type=subroutine',null,false,'75%');
				break;
			case 'node_import':
				genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$behavior->id}&node_id=' + encodeURIComponent(node_id),null,false,'50%');
				break;
			case 'node_export':
				genericAjaxPopup('export_nodes','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$behavior->id}&node_id=' + encodeURIComponent(node_id),null,false,'50%');
				break;
			case 'tree_import':
				genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$behavior->id}',null,false,'50%');
				break;
		}
	};

	$frm.on('click', function(e) {
		const tile = e.target.closest('.cerb-behavior-node');

		if(!tile || !$frm[0].contains(tile))
			return;

		// Don't pop the menu on the click that ends a drag (see tree.tpl).
		if(tile.closest('div.node').classList.contains('dragged'))
			return;

		const node_id = tile.getAttribute('data-node-id');

		if(nodeMenu) {
			nodeMenu.destroy();
			nodeMenu = null;
		}

		genericAjaxGet('nodeMenu{$tab_uniqid}','c=profiles&a=invoke&module=behavior&action=renderDecisionNodeMenu&id=' + encodeURIComponent(node_id) + '&trigger_id={$behavior->id}', function() {
			const ul = document.querySelector('#nodeMenu{$tab_uniqid} > ul');

			if(!ul || !(window.CerbUI && CerbUI.Menu))
				return;

			// Reuse the proven peek mechanism for "Edit Behavior"; refresh the tree on save.
			$(ul).find('.cerb-peek-trigger')
				.cerbPeekTrigger()
				.on('cerb-peek-saved', function() {
					refreshTrees();
				})
				.on('cerb-peek-deleted', function() {
					document.location = '{devblocks_url}{/devblocks_url}';
				});

			nodeMenu = new CerbUI.Menu(ul, {
				onRenderItem: renderNodeMenuIcon,
				onSelect: dispatchNodeMenuAction
			});

			nodeMenu.open(tile);
		});
	});
});
{/if}
</script>
