{$uniq_id = "menu{uniqid()}"}

<div id="div{$uniq_id}">

<input type="hidden" name="oper" value="between">

<b>{$translate->_('search.operator')|capitalize}:</b><br>

<blockquote style="margin:5px;">
	<select name="oper">
		<option value="between" {if $param && $param->operator=='between'}selected="selected"{/if}>{'search.date.between'|devblocks_translate|lower}</option>
		<option value="not between" {if $param && $param->operator=='not between'}selected="selected"{/if}>{$translate->_('search.date.between.not')}</option>
		<option value="equals or null" {if $param && $param->operator=='equals or null'}selected="selected"{/if}>{$translate->_('search.oper.null')}</option>
	</select>
</blockquote>

<div class="date_range" style="display:{if ($param && $param->operator == 'equals or null')}none{else}block{/if};">
	<b>{$translate->_('search.value')|capitalize}:</b><br>
	
	<blockquote style="margin:5px;">
		<input type="text" id="searchDateFrom" name="from" size="20" value="{if !is_null($param->value.0)}{$param->value.0}{/if}" style="width:98%;"><br>
		-{$translate->_('search.date.between.and')}-
		<br>
		<input type="text" id="searchDateTo" name="to" size="20" value="{if !is_null($param->value.1)}{$param->value.1}{else}now{/if}" style="width:98%;">
		<br>
		<br>
		{$translate->_('search.date.examples')|escape|nl2br nofilter}
	</blockquote>
</div>

</div>

<script type="text/javascript">
devblocksAjaxDateChooser('#searchDateFrom');
devblocksAjaxDateChooser('#searchDateTo');

$('#div{$uniq_id} select[name=oper]').change(function(e) {
	var $div = $('#div{$uniq_id}');
	var $select = $(this);
	var val = $select.val();
	
	if(val == 'equals or null') {
		$div.find('div.date_range').hide();
	} else {
		$div.find('div.date_range').show();
	}
});
</script>
