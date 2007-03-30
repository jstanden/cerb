<table cellpadding="0" cellspacing="0" border="0" class="tableOrange" width="220" class="tableBg">
	<tr>
		<td class="tableThOrange" nowrap="nowrap"> <img src="{devblocks_url}images/window_view.gif{/devblocks_url}"> {$translate->_('dashboard.dashboards')|capitalize}</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%" class="tableBg">
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
				      	<b>{$translate->_('dashboard')|capitalize}:</b> 
				      	<select name="">
				      		<option value="">-- {$translate->_('dashboard.choose_dashboard')|lower} --
				      		{foreach from=$dashboards item=dashboard}
				      			<option value="{$dashboard->id}">{$dashboard->name}
				      		{/foreach}
				      		<option value="">-- {$translate->_('dashboard.add_dashboard')|lower} --
				      	</select>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="{devblocks_url}c=tickets&a=addView{/devblocks_url}">{$translate->_('dashboard.add_view')|lower}</a>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="#">{$translate->_('dashboard.modify')|lower}</a>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="{devblocks_url}c=tickets{/devblocks_url}">{$translate->_('common.refresh')|lower}</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
