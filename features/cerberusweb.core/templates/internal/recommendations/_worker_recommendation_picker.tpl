{$menu_button = "btn{uniqid()}"}

{$active_workers = DAO_Worker::getAllActive()}
{$online_workers = DAO_Worker::getAllOnline()}

<table cellpadding="2" cellspacing="0" border="0" width="100%" class="recommendations-active">
	<thead>
	<tr>
		<td>
			<b>{'common.worker'|devblocks_translate|capitalize}</b>
		</td>
		<td>
			<b>{'common.availability'|devblocks_translate|capitalize} (24h)</b>
		</td>
		<td>
			<abbr title="Open assignments / Recommendations / Unread notifications" style="font-weight:bold;">Workload</abbr>
		</td>
		<td>
			<b>{'common.responsibility'|devblocks_translate|capitalize}</b>
		</td>
	</tr>
	</thead>
		
	<tbody>
		{foreach from=$recommended_workers item=worker_id}
		{$worker = $active_workers.$worker_id}
		{$availability = $worker->getAvailabilityAsBlocks()}
		{if isset($recommendation_scores.$worker_id)}
			{$recommendation = $recommendation_scores.$worker_id}
		{/if}
		<tr data-worker-id="{$worker_id}" data-score="{$recommendation.score}">
			<td>
				{if isset($online_workers.$worker_id)}
				<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(0,180,0);margin-right:5px;line-height:10px;"></div>
				{else}
				<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(220,220,220);margin-right:5px;line-height:10px;"></div>
				{/if}
				{if $owner_id == $worker_id}{/if}
				<a href="javascript:;" class="item no-underline"><b>{$worker->getName()}</b></a>
				<small>{$worker->title}</small>
				<input type="hidden" name="recommended_workers[]" value="{$worker->id}">
			</td>
			<td>
				<div>
					<!--
					{foreach from=$availability.blocks item=block name=blocks}
					--><div style="width:{{$block.length/$availability.ticks*100}|round}px;height:10px;{if $block.available}background-color:rgb(0,200,0);{else}background-color:rgb(220,220,220);{/if}display:inline-block;{if $smarty.foreach.blocks.first && $smarty.foreach.blocks.last}border-radius:10px;{elseif $smarty.foreach.blocks.first}border-radius:10px 0px 0px 10px;{elseif $smarty.foreach.blocks.last}border-radius:0px 10px 10px 0px;{/if}" title="{$block.start|devblocks_date} - {$block.end|devblocks_date}"></div><!--
					{/foreach}-->
				</div>
			</td>
			<td nowrap="nowrap">
				{$num_assignments = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_TICKET}|default:0 + $workloads.$worker_id.records.{CerberusContexts::CONTEXT_TASK}|default:0}
				{$num_recommendations = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_RECOMMENDATION}|default:0}
				{$num_unread_notifications = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_NOTIFICATION}|default:0}
				
				{if $num_assignments}<span style="color:green;font-weight:bold;">{$num_assignments}</span>{else}0{/if}
				<span style="color:rgb(200,200,200);"> / </span>
				{if $num_recommendations}<span style="font-weight:bold;">{$num_recommendations}</span>{else}0{/if}
				<span style="color:rgb(200,200,200);"> / </span>
				{if $num_unread_notifications}<span style="color:red;font-weight:bold;">{$num_unread_notifications}</span>{else}0{/if}
			</td>
			<td nowrap="nowrap">
				<div style="position:relative;margin:0px 5px;width:70px;height:10px;background-color:rgb(230,230,230);border-radius:10px;display:inline-block;">
					<span style="display:inline-block;background-color:rgb(200,200,200);height:14px;width:1px;position:absolute;top:-2px;margin-left:1px;left:50%;"></span>
					<div style="position:relative;margin-left:-6px;top:-2px;left:{$recommendation.score}%;width:14px;height:14px;border-radius:14px;background-color:{if $recommendation.score < 50}rgb(230,70,70);{elseif $recommendation.score > 50}rgb(0,200,0);{else}rgb(175,175,175);{/if}"></div>
				</div>
			
				<a href="javascript:;" class="delete"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>
			</td>
		</tr>
		{/foreach}
	</tbody>
	
</table>

<div id="{$menu_button}" class="badge badge-lightgray" style="margin-top:10px;cursor:pointer;"><a href="javascript:;" style="text-decoration:none;color:rgb(50,50,50);">{'common.add'|devblocks_translate|capitalize} &#x25be;</a><input type="hidden" name="recommendations_expanded" value="{if $recommendations_expanded}1{else}0{/if}"></div>

<div class="cerb-popupmenu" style="{if $recommendations_expanded}display:block;{/if}max-height:200px;overflow-y:auto;border:0;-moz-box-shadow: 0px 5px 15px 0px #afafaf;-webkit-box-shadow: 0px 5px 15px 0px #afafaf;box-shadow: 0px 5px 15px 0px #afafaf;">
	<div>
		<input type="text" class="input_search" size="45" style="width:80%;">
		<button type="button" class="refresh" style="display:none;"><span class="glyphicons glyphicons-refresh"></span></button>
	</div>

	<table cellpadding="2" cellspacing="0" border="0" width="100%" class="recommendations-picker">
		<thead>
		<tr>
			<td>
				<b>{'common.worker'|devblocks_translate|capitalize}</b>
			</td>
			<td>
				<b>{'common.availability'|devblocks_translate|capitalize} (24h)</b>
			</td>
			<td>
				<abbr title="Open assignments / Recommendations / Unread notifications" style="font-weight:bold;">Workload</abbr>
			</td>
			<td>
				<b>{'common.responsibility'|devblocks_translate|capitalize}</b>
			</td>
		</tr>
		</thead>
		
		<tbody>
			{foreach from=$recommendation_scores item=recommendation key=worker_id}
			{if !in_array($worker_id, $recommended_workers)}
			{$worker = $active_workers.$worker_id}
			{$availability = $worker->getAvailabilityAsBlocks()}
				<tr data-worker-id="{$worker_id}" data-score="{$recommendation.score}">
					<td>
						{if isset($online_workers.$worker_id)}
						<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(0,180,0);margin-right:5px;line-height:10px;"></div>
						{else}
						<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(220,220,220);margin-right:5px;line-height:10px;"></div>
						{/if}
						<a href="javascript:;" class="item no-underline"><b>{$worker->getName()}</b></a>
						<small>{$worker->title}</small>
					</td>
					<td>
						<div>
						<!--
						{foreach from=$availability.blocks item=block name=blocks}
						--><div style="width:{{$block.length/$availability.ticks*100}|round}px;height:10px;{if $block.available}background-color:rgb(0,200,0);{else}background-color:rgb(220,220,220);{/if}display:inline-block;{if $smarty.foreach.blocks.first && $smarty.foreach.blocks.last}border-radius:10px;{elseif $smarty.foreach.blocks.first}border-radius:10px 0px 0px 10px;{elseif $smarty.foreach.blocks.last}border-radius:0px 10px 10px 0px;{/if}" title="{$block.start|devblocks_date} - {$block.end|devblocks_date}"></div><!--
						{/foreach}-->
						</div>
					</td>
					<td nowrap="nowrap">
						{$num_assignments = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_TICKET}|default:0 + $workloads.$worker_id.records.{CerberusContexts::CONTEXT_TASK}|default:0}
						{$num_recommendations = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_RECOMMENDATION}|default:0}
						{$num_unread_notifications = $workloads.$worker_id.records.{CerberusContexts::CONTEXT_NOTIFICATION}|default:0}

						{if $num_assignments}<span style="color:green;font-weight:bold;">{$num_assignments}</span>{else}0{/if}
						<span style="color:rgb(200,200,200);"> / </span>
						{if $num_recommendations}<span style="font-weight:bold;">{$num_recommendations}</span>{else}0{/if}
						<span style="color:rgb(200,200,200);"> / </span>
						{if $num_unread_notifications}<span style="color:red;font-weight:bold;">{$num_unread_notifications}</span>{else}0{/if}
					</td>
					<td>
						<div style="position:relative;margin:0px 5px;width:70px;height:10px;background-color:rgb(230,230,230);border-radius:10px;display:inline-block;">
							<span style="display:inline-block;background-color:rgb(200,200,200);height:14px;width:1px;position:absolute;top:-2px;margin-left:1px;left:50%;"></span>
							<div style="position:relative;margin-left:-6px;top:-2px;left:{$recommendation.score}%;width:14px;height:14px;border-radius:14px;background-color:{if $recommendation.score < 50}rgb(230,70,70);{elseif $recommendation.score > 50}rgb(0,200,0);{else}rgb(175,175,175);{/if}"></div>
						</div>
						
						<a href="javascript:;" class="delete" style="display:none;"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>
					</td>
				</tr>
			{/if}
			{/foreach}
		</tbody>
		
	</table>
</div>

<script type="text/javascript">
$(function() {
// Menu
var $menu_trigger = $('#{$menu_button}');
var $menu_expanded = $('#{$menu_button}').find('input:hidden');
var $menu = $menu_trigger.siblings('div.cerb-popupmenu');
var $table = $menu.siblings('table.recommendations-active');
var $search = $menu.find('input.input_search');
var $picker = $menu.find('table.recommendations-picker');

$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			var $menu = $(this).data('menu');

			if($menu.is(':visible')) {
				$menu_expanded.val('0');
				$menu.hide();
				return;
			}

			$menu
				.show()
				;
			
			$menu_expanded.val('1');
			
			$search
				.focus()
				.select()
				;
		}
	)
;

$search.keypress(
	function(e) {
		var code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			e.preventDefault();
			e.stopPropagation();
			$(this).select().focus();
			return false;
		}
	}
);
	
$search.keyup(
	function(e) {
		var term = $(this).val().toLowerCase();
		$menu.find('tbody > tr > td > a.item').each(function(e) {
			var $tr = $(this).closest('tr');
			
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$tr.show();
			} else {
				$tr.hide();
			}
		});
	}
);

$table.on('click', 'tbody a.delete', function() {
	var $tr = $(this).closest('tr');
	var worker_id = $tr.attr('data-worker-id');
	
	$tr.find('a.delete').hide();
	
	$tr.find('input:hidden').remove();
	
	// Ajax remove recommendation from ticket
	genericAjaxGet('', 'c=internal&a=handleSectionAction&section=recommendations&action=removeRecommendation&context={$context}&context_id={$context_id}&worker_id=' + worker_id);
	
	$tr.appendTo($picker.find('tbody'));
});

$picker.on('click', 'tbody a.item', function() {
	var $tr = $(this).closest('tr');
	var $input = $('<input type="hidden">');
	var worker_id = $tr.attr('data-worker-id');
	var score = $tr.attr('data-score');
	
	$input.attr('name', 'recommended_workers[]');
	$input.attr('value', worker_id);
	$input.insertAfter($(this));
	
	$tr.find('a.delete').show();
	
	// Ajax add recommendation to ticket
	genericAjaxGet('', 'c=internal&a=handleSectionAction&section=recommendations&action=addRecommendation&context={$context}&context_id={$context_id}&worker_id=' + worker_id + '&score=' + score);
	
	$tr.appendTo($table.find('tbody'));
});

$menu.on('click', 'button.refresh', function() {
	genericAjaxPost($table.closest('form'), $table.closest('div'), 'c=internal&a=handleSectionAction&section=recommendations&action=renderPicker&context={$context}&context_id={$context_id}');
});

$table.closest('form').on('cerb-form-update', function() {
	$menu.find('button.refresh').click();
});

});
</script>