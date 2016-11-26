{$menu_domid = "menu{uniqid()}"}

<input type="hidden" name="oper" value="like">

<b>{'search.oper.matches'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{if is_string($param->value)}{$param->value}{/if}" style="width:100%;"><br>
</blockquote>
