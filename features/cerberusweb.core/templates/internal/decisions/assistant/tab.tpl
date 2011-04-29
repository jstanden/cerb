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
<div id="decisionTree{$trigger_id}">
	{$event = $events.{$trigger->event_point}}
	{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$trigger event=$event}
</div>
{/foreach}
</div>

<div id="nodeMenu" style="display:none;position:absolute;z-index:5;"></div>

<script type="text/javascript">
	$('#frmTrigger SELECT[name=event_point]').change(function() {
		genericAjaxPost('frmTrigger',null,null,function(o) { 
			document.location.reload(); 
		});
	});

	//$('#triggers DIV.branch').sortable({ items:'DIV.node', placeholder:'ui-state-highlight' });
	
	function decisionNodeMenu(element, node_id, trigger_id) {
		genericAjaxGet('', 'c=internal&a=showDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
			$position = $(element).position();
			$('#nodeMenu')
				.unbind()
				.hide()
				.html('')
				.css('top',$position.top+($(element).height()*0.75))
				.css('left',$position.left)
				.html(html)
				.fadeIn('fast')
				;
			$('#nodeMenu')
				.hover(
					function() {},
					function() {
						$(this).hide(); 
					}
				)
				.click(function(e) {
					$(this).hide();
				})
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
	
	//$('#triggers fieldset div.branch.switch').sortable({ items:'> DIV.outcome', placeholder:'ui-state-highlight', distance: 15, handle:'span.handle', connectWith:'DIV.branch.switch' });
	//$('#triggers fieldset div.branch').sortable({ items:'> DIV.action', placeholder:'ui-state-highlight', distance: 15, handle:'span.handle', connectWith:'DIV.branch.outcome' });
</script>
