{$menu_button = "btn{uniqid()}"}

<ul class="chooser-container bubbles">
{if isset($params.$param_name)}
{foreach from=$params.$param_name item=val_key}
	{if isset($values_to_contexts.$val_key)}
		{$var_data = $values_to_contexts.$val_key}
		{if !empty($var_data)}
		<li>{$var_data.label}<input type="hidden" name="{$namePrefix}[{$param_name}][]" value="{$val_key}"><a data-cerb-link="remove_parent"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if}
	{/if}
{/foreach}
{/if}
</ul>

<div id="{$menu_button}" class="badge badge-lightgray" style="cursor:pointer;"><a style="text-decoration:none;color:var(--cerb-color-background-contrast-50);">Add &#x25be;</a></div>

<ul class="cerb-popupmenu" style="max-height:200px;overflow-y:auto;border:0;">
	<li class="filter"><input type="text" class="input_search" size="45"></li>

	<li><b>Placeholders</b></li>

	{foreach from=$values_to_contexts item=var_data key=var_key}
		{if $var_data.label}<li class="item" key="{$var_key}" style="padding-left:20px;"><a>{$var_data.label}</a></li>{/if}
	{/foreach}
</ul>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	// Menu
	let $menu_trigger = $('#{$menu_button}');
	let $menu = $menu_trigger.nextAll('ul.cerb-popupmenu');
	let $bubbles = $menu.prevAll('ul.chooser-container.bubbles');

	$menu_trigger.data('menu', $menu);

	$bubbles.find('[data-cerb-link=remove_parent]').on('click', Devblocks.onClickRemoveParent);

	$menu_trigger
		.click(
			function(e) {
				e.stopPropagation();

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
			let code = e.keyCode || e.which;
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
			e.stopPropagation();
			let term = $(this).val().toLowerCase();
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
		let $li = $(this).closest('li');
		let $key = $li.attr('key');

		if($bubbles.find('li input:hidden[value="' + $key + '"]').length > 0)
			return;

		let $bubble = $('<li></li>');
		$bubble.append($li.find('a').text());
		$bubble.append($('<input type="hidden">').attr('name', '{$namePrefix}[{$param_name}][]').attr('value', $key));
		let $a = $('<a><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($bubble);
		$a.on('click', function(e) {
			e.stopPropagation();
			$(this).parent().remove();
		});

		$bubbles.append($bubble);
	});
});
</script>