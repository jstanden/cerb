<div class="block">
<form method="post" action="{devblocks_url}{/devblocks_url}" id="simulatorForm">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="simulator.config.tab">
<input type="hidden" name="action" value="generateTickets">

<H2>{$translate->_('simulator.ui.cfg.sample_tickets')}</H2>

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

<div id="divSimulatorOutput"></div>
<br>

<button type="button" onclick="document.getElementById('divSimulatorOutput').innerHTML='{$translate->_('simulator.ui.cfg.generate_wait')}';genericAjaxPost('simulatorForm','divSimulatorOutput');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="top"> {$translate->_('simulator.ui.cfg.generate')}</button>
	
</form>
</div>
