{$menu_divid = "{uniqid()}"}

<form action="javascript:;" id="frmTrigger" onsubmit="return false;">

<div style="float:left;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle"></span> Create Behavior</button>
</div>

</form>
<br clear="all">

<div id="triggers_by_event">
	{foreach from=$events key=event_point item=event}
	<div id="event_{$event_point|replace:'.':'_'}" style="margin-left:15px;{if !isset($triggers_by_event.$event_point)}display:none;{/if}">
		<h3 style="margin-left:-15px;">{$event->name}</h3>
		{foreach from=$triggers_by_event.$event_point key=trigger_id item=trigger}
		<form id="decisionTree{$trigger_id}" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="trigger_id[]" value="{$trigger_id}">
			{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$trigger event=$event}
		</form>
		{/foreach}
	</div>
	{/foreach}
</div>

<div id="nodeMenu{$menu_divid}" style="display:none;position:absolute;z-index:5;"></div>

<script type="text/javascript">
	$('#nodeMenu{$menu_divid}').appendTo('body');
	
	$('#frmTrigger BUTTON.add').click(function() {
		$popup = genericAjaxPopup('node_trigger','c=internal&a=showDecisionPopup&trigger_id=0&context={$context}&context_id={$context_id}',null,false,'500');
		
		$popup.one('trigger_create',function(event) {
			if(null == event.event_point || null == event.trigger_id)
				return;
			
			$event = $('#event_' + event.event_point.replace(/\./g,'_'));
			$tree = $('<form id="decisionTree'+event.trigger_id+'"></form>');
			$event.show().append($tree);
			$(window).scrollTop($tree.position().top);
			
			genericAjaxGet('decisionTree'+event.trigger_id, 'c=internal&a=showDecisionTree&id='+event.trigger_id);
		});
	});
	
	function decisionNodeMenu(element) {
		$this = $(element);
		
		node_id = $this.attr('node_id');
		trigger_id = $this.attr('trigger_id');
		
		if($this.closest('div.node').hasClass('dragged'))
			return;
		
		genericAjaxGet('', 'c=internal&a=showDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
			$position = $(element).offset();
			$('#nodeMenu{$menu_divid}')
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
			$('#nodeMenu{$menu_divid}')
				.hover(
					function() {
					},
					function() {
						$(this).hide();
					}
				)
				.click(function(e) {
					$(this).hide();
				})
				.find('ul li')
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
	
	$('#triggers_by_event').find('> DIV')
		.sortable({
			items: '> FORM',
			handle: 'legend',
			placeholder:'ui-state-highlight',
			distance: 15,
			stop:function(event, ui) {
				$container = $(this);
				triggers = $container.find("> form > input:hidden[name='trigger_id[]']").map(function() {
					return "trigger_id[]=" + $(this).val();
				}).get().join('&');
				
				genericAjaxGet('','c=internal&a=reorderTriggers&' + triggers);
			}
		})
		;
	
</script>
