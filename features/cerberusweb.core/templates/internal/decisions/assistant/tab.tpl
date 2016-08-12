{$tab_uniqid = "{uniqid()}"}

{$is_writeable = $va->isWriteableByActor($active_worker)}

{if $is_writeable}
	<form action="javascript:;" id="frmTrigger{$tab_uniqid}" onsubmit="return false;">
	
	<div style="float:left;">
		<button type="button" class="add"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> Create Behavior</button>
	</div>
	
	</form>
	<br clear="all">
{/if}

<div id="triggers_by_event{$tab_uniqid}">
	{foreach from=$events key=event_point item=event}
	<div id="event_{$event_point|replace:'.':'_'}_{$tab_uniqid}" style="margin-left:15px;{if !isset($triggers_by_event.$event_point)}display:none;{/if}">
		<h3 style="margin-left:-15px;">{$event->name}</h3>
		{foreach from=$triggers_by_event.$event_point key=trigger_id item=trigger}
		<form id="decisionTree{$trigger_id}" action="javascript:;" onsubmit="return false;">
			<input type="hidden" name="trigger_id[]" value="{$trigger_id}">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$trigger event=$event is_writeable=$is_writeable}
		</form>
		{/foreach}
	</div>
	{/foreach}
</div>

<div id="nodeMenu{$tab_uniqid}" style="display:none;position:absolute;z-index:5;"></div>

<script type="text/javascript">
{if $is_writeable}
function decisionNodeMenu(element) {
	var $this = $(element);
	
	var node_id = $this.attr('node_id');
	var trigger_id = $this.attr('trigger_id');
	
	if($this.closest('div.node').hasClass('dragged'))
		return;
	
	genericAjaxGet('', 'c=internal&a=showDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
		var $position = $(element).offset();
		$('#nodeMenu{$tab_uniqid}')
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
		$('#nodeMenu{$tab_uniqid}')
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
				var $target = $(e.target);
				if(!$target.is('li'))
					return;
				
				e.stopPropagation();
				$target.find('A').first().click();
			})
		;
	});
}
{/if}

$(function() {
	$('#nodeMenu{$tab_uniqid}').appendTo('body');
	
	{if $is_writeable}
	$('#frmTrigger{$tab_uniqid} BUTTON.add').click(function() {
		var $popup = genericAjaxPopup('node_trigger','c=internal&a=showDecisionPopup&trigger_id=0&va_id={$va->id}{if isset($only_event_ids)}&only_event_ids={$only_event_ids}{/if}',null,false,'50%');
		
		$popup.one('trigger_create',function(event) {
			if(null == event.event_point || null == event.trigger_id)
				return;
			
			var $event = $('#event_' + event.event_point.replace(/\./g,'_') + '_{$tab_uniqid}');
			var $tree = $('<form></form>').attr('id', 'decisionTree'+event.trigger_id);
			$event.show().append($tree);
			
			setTimeout(function($tree) {
				$(window).scrollTop($tree.position().top);
				$tree.find('legend').effect('highlight', { }, 1000);
			}, 250, $tree);
			
			genericAjaxGet('decisionTree'+event.trigger_id, 'c=internal&a=showDecisionTree&id='+event.trigger_id);
		});
	});
	{/if}
	
	{if $is_writeable}
	$('#triggers_by_event{$tab_uniqid}').find('> DIV')
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
	{/if}
});
	
</script>