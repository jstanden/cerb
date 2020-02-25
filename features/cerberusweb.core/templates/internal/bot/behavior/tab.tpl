{$tab_uniqid = "{uniqid()}"}
{$is_writeable = Context_TriggerEvent::isWriteableByActor($behavior, $active_worker)}

<form id="decisionTree{$behavior->id}" action="javascript:;" style="margin-top:10px;" onsubmit="return false;">
	<input type="hidden" name="trigger_id[]" value="{$behavior->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$behavior event=$event is_writeable=$is_writeable}
</form>

<div id="nodeMenu{$tab_uniqid}" style="display:none;position:absolute;z-index:50000;"></div>

<script type="text/javascript">
{if $is_writeable && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_BEHAVIOR}.update")}
$(function() {
	// [TODO] This is not guaranteed unique (use tab_uniqid)
	var $frm = $('#decisionTree{$behavior->id}');
	
	$frm.on('click', function(e) {
		var $target = $(e.target);
		
		if(!($target.is('a') && null != $target.attr('node_id')))
			return;
		
		var node_id = $target.attr('node_id');
		var trigger_id = $target.attr('trigger_id');
		
		if($target.closest('div.node').hasClass('dragged'))
			return;
		
		genericAjaxGet('', 'c=profiles&a=invoke&module=behavior&action=renderDecisionNodeMenu&id='+node_id+'&trigger_id='+trigger_id, function(html) {
			var $position = $target.offset();
			$('#nodeMenu{$tab_uniqid}')
				.appendTo('body')
				.unbind()
				.hide()
				.html('')
				.css('position','absolute')
				.css('top',$position.top+$target.height())
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
	});
});
{/if}
</script>