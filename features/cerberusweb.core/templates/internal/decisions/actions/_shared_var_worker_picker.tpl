{$menu_button = "btn{uniqid()}"}

<ul class="chooser-container bubbles">
{if isset($params.$param_name)}
{foreach from=$params.$param_name item=worker_id}
	{if !is_numeric($worker_id) && isset($values_to_contexts.$worker_id)}
		{$var_data = $values_to_contexts.$worker_id}
		{if !empty($var_data)}
		<li>{$var_data.label}<input type="hidden" name="{$namePrefix}[{$param_name}]{if !$single}[]{/if}" value="{$worker_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if}
	
	{elseif is_numeric($worker_id) && isset($workers.$worker_id)}
		{$context_worker = $workers.$worker_id}
		{if !empty($context_worker)}
		<li>
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$context_worker->id}{/devblocks_url}?v={$context_worker->updated}">
			{$context_worker->getName()}
			<input type="hidden" name="{$namePrefix}[{$param_name}]{if !$single}[]{/if}" value="{$context_worker->id}">
			<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
		</li>
		{/if}
	{/if}
{/foreach}
{/if}
</ul>

<div id="{$menu_button}" class="badge badge-lightgray" style="cursor:pointer;"><a href="javascript:;" style="text-decoration:none;color:var(--cerb-color-background-contrast-50);">{'common.add'|devblocks_translate|capitalize} &#x25be;</a></div>

<ul class="cerb-popupmenu" style="max-height:200px;overflow-y:auto;border:0;">
	<li class="filter"><input type="text" class="input_search" size="45"></li>

	{if !empty($values_to_contexts)}
	<li><b>Placeholders</b></li>

	{foreach from=$values_to_contexts item=var_data key=var_key}
		{if $var_data.context == "{CerberusContexts::CONTEXT_WORKER}"}
		<li class="item" key="{$var_key}" style="padding-left:20px;">
			<a href="javascript:;">{$var_data.label}</a>
		</li>
		{/if}
	{/foreach}

	<li><b>Workers</b></li>
	{/if}
		
	{$active_workers = DAO_Worker::getAllActive()}
	{foreach from=$active_workers item=worker key=worker_id}
		<li class="item" key="{$worker_id}" style="padding-left:20px;">
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}">
			<a href="javascript:;">{$worker->getName()}</a>
		</li>
	{/foreach}
</ul>

<script type="text/javascript">
$(function() {
// Menu
var $menu_trigger = $('#{$menu_button}');
var $menu = $menu_trigger.nextAll('ul.cerb-popupmenu');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			var $menu = $(this).data('menu');

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
		var code = e.keyCode || e.which;
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
		var term = $(this).val().toLowerCase();
		var $menu = $(this).closest('ul.cerb-popupmenu');
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
	var $li = $(this).closest('li');
	var $menu = $(this).closest('ul.cerb-popupmenu')
	var $bubbles = $menu.prevAll('ul.chooser-container.bubbles');
	
	var $key = $li.attr('key');
	
	{if $single}
	$bubbles.find('> li').remove();
	{else}
	if($bubbles.find('li input:hidden[value="' + $key + '"]').length > 0)
		return;
	{/if}
	
	var $bubble = $('<li></li>');
	
	if($li.find('img.cerb-avatar'))
		$bubble.append($li.find('img.cerb-avatar').clone());
	
	$bubble.append($li.find('a').text());
	$bubble.append($('<input type="hidden">').attr('name', '{$namePrefix}[{$param_name}]{if !$single}[]{/if}').attr('value', $key));
	$bubble.append($('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>'));
	
	$bubbles.append($bubble);
});

});
</script>