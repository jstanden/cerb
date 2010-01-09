<input type="hidden" name="oper" value="between">

<b>{$translate->_('search.date.between')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="from" size="16"><button type="button" onclick="ajax.getDateChooser('dateSearchFrom',this.form.from);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button><br>
	<div id="dateSearchFrom" style="display:none;position:absolute;z-index:1;"></div>
	-{$translate->_('search.date.between.and')}-<br>
	<input type="text" name="to" size="16" value="now"><button type="button" onclick="ajax.getDateChooser('dateSearchTo',this.form.to);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button><br>
	<div id="dateSearchTo" style="display:none;position:absolute;z-index:1;"></div>
	<br>
	{$translate->_('search.date.examples')|nl2br}
</blockquote>

