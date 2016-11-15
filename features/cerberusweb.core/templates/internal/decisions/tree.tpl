{$tree_data = $trigger->getDecisionTreeData()}
{$tree_nodes = $tree_data.nodes}
{$tree_hier = $tree_data.tree}
{$tree_depths = $tree_data.depths}

<div class="node trigger" style="margin-left:10px;{if $trigger->is_disabled}opacity:0.5;{/if}">
	<input type="hidden" name="node_id" value="0">
	<div class="badge badge-lightgray">
		<a href="javascript:;" onclick="decisionNodeMenu(this);" node_id="0" trigger_id="{$trigger->id}" style="font-weight:bold;color:rgb(0,0,0);text-decoration:none;">
			{$event->name}{if $is_writeable} &#x25be;{/if}
		</a>
	</div>
	<div class="branch trigger" style="margin-left:10px;">
		{foreach from=$tree_hier[0] item=child_id}
			{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger->id nodes=$tree_nodes tree=$tree_hier depths=$tree_depths is_writeable=$is_writeable}
		{/foreach}
	</div>
</div>

{if $is_writeable}
<script type="text/javascript">
$(function() {
	
$('#decisionTree{$trigger->id} DIV.node').draggable({
	revert:"invalid",
	revertDuration:250,
	cursor:'pointer',
	handle:'> div.badge',
	helper:'clone',
	distance:5,
	opacity:0.80,
	start:function(e,ui) {
		$(this).addClass('dragged');
	},
	stop:function(e,ui) {
		var $dragged = $(this);
		setTimeout(function() {
			$dragged.removeClass('dragged');
		}, 2000);
	}
});

$('#decisionTree{$trigger->id} DIV.node.trigger > DIV.badge').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action, #decisionTree{$trigger->id} DIV.node.loop, #decisionTree{$trigger->id} DIV.node.subroutine",
	activate:function(e,ui) {
		$(this).addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).removeClass('selected');
	},
	drop:function(e,ui) {
		var $node = $(this).closest('DIV.node');
		$node.find('> DIV.branch').prepend(ui.draggable);
		
		var child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		var parent_id = $node.find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
		return true;
	}
});

$('#decisionTree{$trigger->id} DIV.node.subroutine > DIV.badge').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action, #decisionTree{$trigger->id} DIV.node.loop",
	activate:function(e,ui) {
		$(this).addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).removeClass('selected');
	},
	drop:function(e,ui) {
		var $node = $(this).closest('DIV.node');
		$node.find('> DIV.branch').prepend(ui.draggable);
		
		var child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		var parent_id = $node.find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
		return true;
	}
});

$('#decisionTree{$trigger->id} DIV.node.switch > DIV.badge').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.outcome",
	activate:function(e,ui) {
		$(this).addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).removeClass('selected');
	},
	drop:function(e,ui) {
		var $node = $(this).closest('DIV.node');
		$node.find('> DIV.branch').prepend(ui.draggable);
		
		var child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		var parent_id = $node.find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
		return true;
	}
});

$('#decisionTree{$trigger->id} DIV.node.loop > DIV.badge').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action, #decisionTree{$trigger->id} DIV.node.loop",
	activate:function(e,ui) {
		$(this).addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).removeClass('selected');
	},
	drop:function(e,ui) {
		var $node = $(this).closest('DIV.node');
		$node.find('> DIV.branch').prepend(ui.draggable);
		
		var child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		var parent_id = $node.find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
		return true;
	}
});

$('#decisionTree{$trigger->id} DIV.node.outcome > DIV.badge').droppable({
	greedy:true,
	tolerance:'pointer',
	accept: "#decisionTree{$trigger->id} DIV.node.switch, #decisionTree{$trigger->id} DIV.node.action, #decisionTree{$trigger->id} DIV.node.loop",
	activate:function(e,ui) {
		$(this).addClass('selected');
	},
	deactivate:function(e,ui) {
		$(this).removeClass('selected');
	},
	drop:function(e,ui) {
		var $node = $(this).closest('DIV.node');
		$node.find('> DIV.branch').prepend(ui.draggable);

		var child_id = $(ui.draggable).find('> input:hidden[name=node_id]').val();
		var parent_id = $node.find('> input:hidden[name=node_id]').val();
		genericAjaxGet('','c=internal&a=reparentNode&child_id=' + child_id + '&parent_id=' + parent_id);
		return true;
	}
});

});
</script>
{/if}