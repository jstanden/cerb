<form action="javascript:;" id="frmTrigger" onsubmit="return false;">

<div style="float:left;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Create Behavior</button>
</div>

<div style="float:right;">
	<b>{'common.filter'|devblocks_translate|capitalize}:</b>
	<select name="event_point">
		<option value=""></option>
		<option value=""> - {'common.all'|devblocks_translate|lower} - </option>
		{foreach from=$events item=event key=event_id}
		<option value="{$event_id}">{$event->name}</option>
		{/foreach}
	</select>
</div>

</form>
<br clear="all">

<div id="triggers" style="margin-top:10px;">
{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior.tpl" triggers=$triggers event=$event}
</div>

<div id="nodeMenu" style="display:none;position:absolute;z-index:5;"></div>

<script type="text/javascript">
	$('#nodeMenu').appendTo('body');

	$('#frmTrigger SELECT[name=event_point]').change(function() {
		genericAjaxGet('triggers','c=internal&a=showDecisionEventBehavior&context={$context}&context_id={$context_id}&event_point=' + $(this).val());
		$(this).blur();
	});
	
	$('#frmTrigger BUTTON.add').click(function() {
		$popup = genericAjaxPopup('node_trigger','c=internal&a=showDecisionPopup&trigger_id=0&context={$context}&context_id={$context_id}',null,false,'500');
		
		$popup.one('trigger_create',function(event) {
			if(null == event.trigger_id)
				return;
			$('#frmTrigger SELECT[name=event_point]').val(0);
			$('#triggers').html('').append($('<form id="decisionTree'+event.trigger_id+'"></form>'));
			genericAjaxGet('decisionTree'+event.trigger_id, 'c=internal&a=showDecisionTree&id='+event.trigger_id);
		});
	});
	
	function decisionNodeMenu(element, node_id, trigger_id) {
		if($(element).closest('div.node').hasClass('dragged'))
			return;
		
		genericAjaxGet('', 'c=internal&a=showDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
			$position = $(element).offset();
			$('#nodeMenu')
				.appendTo('body')
				.unbind()
				.hide()
				.html('')
				.css('position','absolute')
				.css('top',$position.top+$(element).height())
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
