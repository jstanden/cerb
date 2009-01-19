<b>Operator:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">in list</option>
		<option value="nin">not in list</option>
	</select>
</blockquote>

<b>Options:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$cfield->options item=opt}
		<label><input type="checkbox" name="options[]" value="{$opt}">{$opt}</label><br>
	{/foreach}
</blockquote>
