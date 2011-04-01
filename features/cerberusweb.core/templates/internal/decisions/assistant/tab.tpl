<form action="javascript:;" style="margin-bottom:5px;" id="frmTrigger" onsubmit="return false;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="createAssistantTrigger">
	<input type="hidden" name="context" value="{$context}">
	<input type="hidden" name="context_id" value="{$context_id}">

	<fieldset>
		<legend>Create New Behavior</legend>
	
		<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
		<select name="event_point">
			<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
			{foreach from=$events item=event key=event_id}
			<option value="{$event_id}">{$event->name}</option>
			{/foreach}
		</select>
	</fieldset>
</form>

<div id="triggers">
{foreach from=$triggers item=trigger key=trigger_id}
<fieldset>
	<legend style="{if $trigger->is_disabled}color:rgb(150,150,150);{/if}">{$trigger->title} {if $trigger->is_disabled}({'common.disabled'|devblocks_translate|capitalize}){/if}</legend>
	
	{$event = $events.{$trigger->event_point}}
	
	{* [TODO] Use cache!! *}
	{$tree_data = $trigger->getDecisionTreeData()}
	{$tree_nodes = $tree_data.nodes}
	{$tree_hier = $tree_data.tree}
	{$tree_depths = $tree_data.depths}
	
	<div class="badge badge-lightgray">
		<a href="javascript:;" onclick="decisionNodeMenu(this,'0','{$trigger_id}');" style="font-weight:bold;color:rgb(0,0,0);text-decoration:none;">
			{$event->name} &#x25be;
		</a>
	</div>
	<div style="margin-left:10px;">
		{foreach from=$tree_hier[0] item=child_id}
			{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger_id data=$tree_data nodes=$tree_nodes tree=$tree_hier depths=$tree_depths}
		{/foreach}
	</div>
</fieldset>
{/foreach}
</div>

<div id="nodeMenu" style="display:none;"></div>

<script type="text/javascript">
	$('#frmTrigger SELECT[name=event_point]').change(function() {
		genericAjaxPost('frmTrigger',null,null,function(o) { 
			document.location.reload(); 
		});
	});

	//$('#triggers DIV.branch').sortable({ items:'DIV.node', placeholder:'ui-state-highlight' });
	
	function decisionNodeMenu(element, node_id, trigger_id) {
		genericAjaxGet('', 'c=internal&a=showDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
			$('#nodeMenu')
				.unbind()
				.hide()
				.html('')
				.appendTo($(element).parent())
				.html(html)
				.fadeIn('fast')
				.parent()
				.hover(
					function() { },
					function() { 
						$(this).find('#nodeMenu').fadeOut('fast'); 
					}
				)
				.click(
					function(e) {
						$(this).find('#nodeMenu').fadeOut('fast');
						//e.preventDefault();
					}
				)
			;
			$('#nodeMenu')
				.find('UL LI')
				.click(function(e) {
					$target = $(e.target);
					if(!$target.is('li'))
						return;
					
					e.stopPropagation();
					$target.find('A').first().click();
				})
			;
		});
	}
	
	//$('DIV.branch').sortable({ items:'DIV', placeholder:'ui-state-highlight' });
</script>
