{$menu_domid = "menu{uniqid()}"}

<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="like" {if $param && $param->operator=='like'}selected="selected"{/if}>{'search.oper.matches'|devblocks_translate}</option>
		<option value="not like" {if $param && $param->operator=='not like'}selected="selected"{/if}>{'search.oper.matches.not'|devblocks_translate}</option>
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
		<option value="!=" {if $param && $param->operator=='!='}selected="selected"{/if}>{'search.oper.equals.not'|devblocks_translate}</option>
		<option value="is null" {if $param && $param->operator=='is null'}selected="selected"{/if}>{'search.oper.null'|devblocks_translate}</option>
	</select>
</blockquote>

<b>{'search.value'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value}" style="width:100%;"><br>
	<i>{'search.string.examples'|devblocks_translate|escape|nl2br nofilter}</i>
</blockquote>

{$placeholders = $view->getPlaceholderLabels()}

<ul id="{$menu_domid}" class="cerb-popupmenu" style="margin-top:5px;max-height:200px;overflow-y:auto;display:none;">
	<li class="filter">
		<input type="text" class="input_search" size="24">
	</li>

	<li><b>Placeholders</b></li>

	{foreach from=$placeholders item=var_data key=var_key}
		{if empty($var_data.context)}
		<li class="item" key="{literal}{{{/literal}{$var_key}{literal}}}{/literal}" style="padding-left:20px;">
			<a href="javascript:;">{$var_data.label}</a>
		</li>
		{/if}
	{/foreach}
</ul>

<script type="text/javascript">
// Menu
$menu = $('#{$menu_domid}');

if($menu.find('> li.item').length > 0)
	$menu.show();

// Focus text input
$menu.closest('td').find('input:text:first').focus().select();

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
	
	$key = $li.attr('key');
	
	$input = $menu.prevAll('blockquote').find('input:text');
	
	$input.insertAtCursor($key);
});

</script>