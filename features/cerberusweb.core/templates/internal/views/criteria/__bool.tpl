<input type="hidden" name="oper" value="=">

<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<label><input type="radio" name="bool" value="1" {if !empty($param->value)}checked="checked"{/if}>{$translate->_('common.yes')|capitalize}</label>
	<label><input type="radio" name="bool" value="0" {if !empty($param) && empty($param->value)}checked="checked"{/if}>{$translate->_('common.no')|capitalize}</label>
	<br>
</blockquote>

