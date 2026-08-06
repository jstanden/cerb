{$tree_data = $trigger->getDecisionTreeData()}
{$tree_nodes = $tree_data.nodes}
{$tree_hier = $tree_data.tree}
{$tree_depths = $tree_data.depths}

{if empty($tree_nodes) && $is_writeable}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Let's build this behavior!</div>
				<div class="cerb-ui-header--subtitle">
					Click on the <div class="badge badge-lightgray" style="color:var(--cerb-color-text);font-weight:bold;">{$event->name}</div> event below to add decisions, actions, loops, and subroutines to your behavior.
					For more information, see <a href="https://cerb.ai/docs/bots/" target="_blank" rel="noopener">Bots</a> in the <a href="https://cerb.ai/docs/home/" target="_blank" rel="noopener">documentation</a>.
				</div>
			</div>
		</div>
	</div>
</div>
{/if}

<div class="node trigger" style="margin-left:10px;{if $trigger->is_disabled}opacity:0.5;{/if}">
	<input type="hidden" name="node_id" value="0">
	<div class="cerb-ui-tile cerb-behavior-node" data-node-id="0" data-trigger-id="{$trigger->id}">
		<span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-blue);"><span class="cerb-icons cerb-icon-bot"></span></span>
		<div class="cerb-ui-tile--text">
			<div class="cerb-ui-tile--kind">event</div>
			<div class="cerb-ui-tile--name">{$event->name}</div>
		</div>
		{if $is_writeable}<span class="cerb-icons cerb-icon-chevron-down cerb-ui-tile--caret"></span>{/if}
	</div>
	<div class="branch trigger" style="margin-left:10px;">
		{foreach from=$tree_hier[0] item=child_id}
			{include file="devblocks:cerb.behaviors.legacy::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger->id nodes=$tree_nodes tree=$tree_hier depths=$tree_depths is_writeable=$is_writeable}
		{/foreach}
	</div>
</div>

{if $is_writeable}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const container = document.getElementById('{$tree_dom_id|default:"decisionTree`$trigger->id`"}');
{literal}
	if(!container || !(window.CerbUI && CerbUI.Draggable && CerbUI.Droppable))
		return;

	// The form element persists across AJAX tree re-renders (only its innerHTML is replaced), so tear down the
	// previous render's drag/drop instances first — else listeners stack up and stale drop zones leak.
	CerbUI.Draggable.from(container)?.destroy();
	(container._cerbDropZones || []).forEach(zone => zone.destroy());
	container._cerbDropZones = [];

	// Child node types each parent node type accepts (mirrors the old jQuery-UI droppable accept selectors).
	const accepts = {
		trigger: ['switch','action','loop','subroutine'],
		subroutine: ['switch','action','loop'],
		switch: ['outcome'],
		loop: ['switch','action','loop'],
		outcome: ['switch','action','loop']
	};

	const reparent = function(childNode, parentNode) {
		parentNode.querySelector(':scope > div.branch').prepend(childNode);

		const formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'behavior');
		formData.set('action', 'reparentNode');
		formData.set('child_id', childNode.querySelector(':scope > input[type=hidden][name=node_id]').value);
		formData.set('parent_id', parentNode.querySelector(':scope > input[type=hidden][name=node_id]').value);

		genericAjaxPost(formData, null, null);
	};

	// A parent node's own tile is its drop zone, accepting the child node types listed above.
	Object.keys(accepts).forEach(function(type) {
		const childTypes = accepts[type];
		container.querySelectorAll('div.node.' + type + ' > .cerb-behavior-node').forEach(function(badge) {
			const parentNode = badge.closest('div.node');
			container._cerbDropZones.push(new CerbUI.Droppable(badge, {
				accept: function(item) {
					return container.contains(item) && childTypes.some(function(t) { return item.classList.contains(t); });
				},
				onDrop: function(info) { reparent(info.item, parentNode); }
			}));
		});
	});

	// Every node drags by its own tile; a tilted clone floats while the original is reparented on drop. The
	// `dragged` class flags the node so the post-drag click doesn't reopen its menu (see behavior/tab.tpl).
	new CerbUI.Draggable(container, {
		items: 'div.node',
		handle: '.cerb-behavior-node',
		onStart: function(node) { node.classList.add('dragged'); },
		onStop: function(node) { setTimeout(function() { node.classList.remove('dragged'); }, 2000); }
	});
{/literal}
});
</script>
{/if}