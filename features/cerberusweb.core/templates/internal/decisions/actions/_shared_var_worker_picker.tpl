{$menu_button = "btn{uniqid()}"}

<ul class="chooser-container bubbles">
{if isset($params.$param_name)}
{foreach from=$params.$param_name item=worker_id}
	{if !is_numeric($worker_id) && isset($values_to_contexts.$worker_id)}
		{$var_data = $values_to_contexts.$worker_id}
		{if !empty($var_data)}
		<li>{$var_data.label}<input type="hidden" name="{$namePrefix}[{$param_name}][]" value="{$worker_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	
	{elseif is_numeric($worker_id) && isset($workers.$worker_id)}
		{$context_worker = $workers.$worker_id}
		{if !empty($context_worker)}
		<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[{$param_name}][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	{/if}
{/foreach}
{/if}
</ul>

<div id="{$menu_button}" class="badge badge-lightgray" style="cursor:pointer;"><a href="javascript:;" style="text-decoration:none;color:rgb(50,50,50);">Add &#x25be;</a></div>

<ul class="cerb-popupmenu" style="max-height:200px;overflow-y:auto;">
	<li class="filter"><input type="text" class="input_search" size="45"></li>

	<li><b>Placeholders</b></li>

	{foreach from=$values_to_contexts item=var_data key=var_key}
		{if $var_data.context == "{CerberusContexts::CONTEXT_WORKER}"}
		<li class="item" key="{$var_key}" style="padding-left:20px;">
			<a href="javascript:;">{$var_data.label}</a>
		</li>
		{/if}
	{/foreach}

	<li><b>Workers</b></li>
		
	{$active_workers = DAO_Worker::getAllActive()}
	{foreach from=$active_workers item=worker key=worker_id}
		<li class="item" key="{$worker_id}" style="padding-left:20px;">
			<a href="javascript:;">{$worker->getName()}</a>
		</li>
	{/foreach}
</ul>

<script type="text/javascript">
// Menu
$menu_trigger = $('#{$menu_button}');
$menu = $menu_trigger.nextAll('ul.cerb-popupmenu');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			$menu = $(this).data('menu');

			if($menu.is(':visible')) {
				$menu.hide();
				return;
			}

			$menu
				.show()
				.find('> li.filter > input.input_search')
				.focus()
				.select()
				;
		}
	)
;

$menu.find('> li.filter > input.input_search').keypress(
	function(e) {
		code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			e.preventDefault();
			e.stopPropagation();
			$(this).select().focus();
			return false;
		}
	}
);
	
$menu.find('> li > input.input_search').keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $(this).closest('ul.cerb-popupmenu');
		$menu.find('> li.item').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
);

$menu.find('> li.item').click(function(e) {
	e.stopPropagation();
	if($(e.target).is('a'))
		return;

	$(this).find('a').trigger('click');
});

$menu.find('> li.item > a').click(function() {
	$li = $(this).closest('li');
	$menu = $(this).closest('ul.cerb-popupmenu')
	$bubbles = $menu.prevAll('ul.chooser-container.bubbles');
	
	$key = $li.attr('key');
	
	if($bubbles.find('li input:hidden[value="' + $key + '"]').length > 0)
		return;
	
	$bubble = $('<li></li>');
	$bubble.append($li.find('a').text());
	$bubble.append($('<input type="hidden" name="{$namePrefix}[{$param_name}][]" value="' + $key + '">'));
	$bubble.append($('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>'));
	
	$bubbles.append($bubble);
});

</script>