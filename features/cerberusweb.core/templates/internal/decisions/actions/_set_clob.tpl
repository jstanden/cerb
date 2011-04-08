<textarea rows="5" cols="60" name="{$namePrefix}[value]" style="width:100%;">{$params.value}</textarea>
<br>
<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert &#x25be;</button>
<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
	<li style="background:none;">
		<input type="text" size="16" class="input_search filter">
	</li>
	{foreach from=$token_labels key=k item=v}
	<li><a href="javascript:;" token="{$k}">{$v}</a></li>
	{/foreach}
</ul>
<button type="button" onclick="genericAjaxPost($(this).closest('form').attr('id'),$(this).nextAll('div.tester').first(),'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=value');">{'common.test'|devblocks_translate|capitalize}</button>
<div class="tester"></div>

<script type="text/javascript">
$condition = $('fieldset#{$namePrefix}');

$condition.find('textarea').elastic();

$menu_trigger = $condition.find('button.cerb-popupmenu-trigger');
$menu = $condition.find('ul.cerb-popupmenu').appendTo('body');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			$menu = $(this).data('menu');
			$menu
				.css('position','absolute')
				.css('top',($(this).offset().top+20)+'px')
				.css('left',$(this).offset().left+'px')
				.show()
				.find('> li input:text')
				.focus()
				.select()
				;
		}
	)
	.bind('remove',
		function(e) {
			$menu = $(this).data('menu');
			$menu.remove();
		}
	)
;

$menu.find('> li > input.filter').keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $(this).closest('ul.cerb-popupmenu');
		$menu.find('> li a').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).parent().show();
			} else {
				$(this).parent().hide();
			}
		});
	}
);

$menu.hover(
	function(e) {
	},
	function(e) {
		$(this).hide();
	}
);

$menu.find('> li').click(function(e) {
	e.stopPropagation();
	if(!$(e.target).is('li'))
		return;

	$(this).find('a').trigger('click');
});

$menu.find('> li > a').click(function() {
	$field=$('fieldset#{$namePrefix} textarea');
	$field.focus().insertAtCursor('{literal}{{{/literal}' + $(this).attr('token') + '{literal}}}{/literal}');
	$(this).closest('ul.cerb-popupmenu').hide();
});
</script>
