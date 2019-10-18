{$menu_id = uniqid()}

{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key} data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul>
					{tree keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<ul id="{$menu_id}" class="menu cerb-float" style="width:300px;display:none;">
{tree keys=$placeholders}
</ul>

<script type="text/javascript">
$(function() {
	var $subpage = $('BODY > DIV.cerb-subpage');
	var $toolbar = $subpage.find('form.toolbar');
	
	var $menu = null;
	var $new_button = null;
	
	$new_button = $('<button type="button"/>')
		.attr('title','{'common.move'|devblocks_translate|capitalize}')
		.append($('<span class="glyphicons glyphicons-inbox"/>'))
		.on('click', function(e) {
			e.stopPropagation();
			
			$menu.toggle();
			
			$menu.position({
				my: 'left top',
				at: 'right top',
				of: $new_button,
				collision: 'fit'
			});
		})
		.appendTo($toolbar);
		;
		
	$menu = $('#{$menu_id}')
		.menu({
			position: { my: "left middle", at: "right middle", collision: "fit" },
			select: function(event, ui) {
				var bucket_id = ui.item.attr('data-token');
				
				if(null == bucket_id)
					return;
				
				$menu.hide();
				
				var params = {
					'c': 'display',
					'a': 'doMove',
					'ticket_id': {$page_context_id},
					'bucket_id': bucket_id
				};

				genericAjaxGet('',$.param(params), function(response) {
					document.location.reload();
				});
			}
		})
		.position({
			my: 'left top',
			at: 'right top',
			of: $new_button,
			collision: 'fit'
		})
		.appendTo($toolbar)
		;
});
</script>