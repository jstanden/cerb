<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="=" {if $param && $param->operator=='='}selected="selected"{/if}>{$translate->_('search.oper.equals')}</option>
		<option value="!=" {if $param && $param->operator=='!='}selected="selected"{/if}>{$translate->_('search.oper.equals.not')}</option>
		<option value="&gt;" {if $param && $param->operator=='>'}selected="selected"{/if}>&gt;</option>
		<option value="&lt;" {if $param && $param->operator=='<'}selected="selected"{/if}>&lt;</option>
	</select>
</blockquote>

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value|devblocks_prettysecs}"><br>
</blockquote>

<div style="margin-top:10px;">
	Examples:
	<ul style="margin:0px 0px 10px 0px;">
		<li>5 seconds</li>
		<li>15 mins</li>
		<li>2 hours</li>
		<li>1 day, 3 hours</li>
		<li>1 week, 2 days, 3 hours</li>
	</ul>
</div>
