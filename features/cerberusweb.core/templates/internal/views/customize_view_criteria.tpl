{$view_params = []}
{$is_custom = isset($is_custom) && $is_custom}

{if $is_custom}
	{* Get required params only from parent worklist *}
	{$workspace_list = $view->getCustomWorklistModel()}
	{if $workspace_list}
	{$view_params = $workspace_list->getParamsRequired()}
	{/if}
{else}
	{$view_params = $view->getEditableParams()}
{/if}
{$parent_div = "viewCustom{if $is_custom}Req{/if}Filters{$view->id}"}

{if $is_custom}
<div class="cerb-filters-list">
{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params readonly=false}
</div>
{/if}

{if !$is_custom}
<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tbody class="summary">
<tr>
	<td>
		{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params readonly=true}
		<script type="text/javascript">
		$('#{$parent_div} TBODY.summary TD:first').hover(
			function() {
				$(this).find('a.delete').show();
			},
			function() {
				$(this).find('a.delete').hide();
			}
		);
		</script>
	</td>
</tr>
</tbody>
</table>
{/if}

<script type="text/javascript">
$(function() {
	var $parent = $('#{$parent_div}');
	
	$parent.find('TBODY.summary > TR > TD:first > div.filters').on('click', function(e) {
		e.stopPropagation();
		
		var $frm = $parent.closest('form');
		$frm.find('tbody.full').toggle();
	});
	
	$parent.find('div.cerb-filters-list').on('click', function(e) {
		e.stopPropagation();
		var $target = $(e.target);
		
		if(!$target.is('span'))
			return;
		
		var $container = $target.closest('div');
		var $checkbox = $container.find('input:checkbox');
		
		if($checkbox.prop('checked')) {
			$checkbox.prop('checked', false);
			$target.css('color', '');
			$container.css('text-decoration', '');
			
		} else {
			$checkbox.prop('checked', true);
			$target.css('color', 'rgb(150,0,0)');
			$container.css('text-decoration', 'line-through');
		}
	});
	
	$parent.find('button.cerb-save').on('click', function(e) {
		e.stopPropagation();

		var $form = $(this).closest('form');

		var formData = new FormData($form[0]);
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'addFilter');
		formData.set('replace', '1');
		{if $is_custom}formData.set('is_custom', '1');{/if}

		genericAjaxPost(formData,'{$parent_div}',null);
	});
});
</script>