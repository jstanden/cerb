{$tab_uniqid = "{uniqid()}"}
{$is_writeable = $va->isWriteableByActor($active_worker)}

<form id="decisionTree{$behavior->id}" action="javascript:;" style="margin-top:10px;" onsubmit="return false;">
	<input type="hidden" name="trigger_id[]" value="{$behavior->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	{include file="devblocks:cerberusweb.core::internal/decisions/tree.tpl" trigger=$behavior event=$event is_writeable=$is_writeable}
</form>

<div id="nodeMenu{$tab_uniqid}" style="display:none;position:absolute;z-index:50000;"></div>

<script type="text/javascript">
{if $is_writeable}
{* [TODO] Redo *}
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
</script>