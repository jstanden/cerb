<h2>{'simulator.common'|devblocks_translate|capitalize}</h2>

<fieldset>
	<legend>{$translate->_('simulator.ui.cfg.sample_tickets')}</legend>

	<form method="post" action="{devblocks_url}{/devblocks_url}" id="simulatorForm">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="simulator">
	<input type="hidden" name="action" value="generateTicketsJson">
	
	<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td>
				<b>{$translate->_('simulator.ui.cfg.to_address')}</b><br>
				<input type="text" name="address" value="{$address}" size="45">
			</td>
			<td>
				<b>{$translate->_('simulator.ui.cfg.flavor')}</b><br>
				<select name="dataset">
					{foreach from=$flavors item=flavor key=flavor_id}
						<option value="{$flavor_id}">{$flavor}
					{/foreach}
				</select>
			</td>
			<td>
				<b>{$translate->_('simulator.ui.cfg.how_many')}</b><br>
				<input type="text" name="how_many" size="4" maxlength="3" value="25">
			</td>
		</tr>
	</table>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite sprite-gear"></span> {$translate->_('simulator.ui.cfg.generate')}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#simulatorForm BUTTON.submit')
	.click(function(e) {
		Devblocks.showSuccess('#simulatorForm div.status', "{$translate->_('simulator.ui.cfg.generate_wait')}", false, false);
		
		genericAjaxPost('simulatorForm','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#simulatorForm div.status',$o.error);
			} else {
				Devblocks.showSuccess('#simulatorForm div.status',$o.message);
			}
		});
	})
	;	
</script>