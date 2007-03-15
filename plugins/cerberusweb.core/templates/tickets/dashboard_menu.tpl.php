<table cellpadding="0" cellspacing="0" border="0" class="tableOrange" width="220" class="tableBg">
	<tr>
		<td class="tableThOrange" nowrap="nowrap"> <img src="{devblocks_url}images/window_view.gif{/devblocks_url}"> {$translate->say('dashboard.dashboards')|capitalize}</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%" class="tableBg">
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
				      	<b>{$translate->say('dashboard')|capitalize}:</b> 
				      	<select name="">
				      		<option value="">-- {$translate->say('dashboard.choose_dashboard')|lower} --
				      		{foreach from=$dashboards item=dashboard}
				      			<option value="{$dashboard->id}">{$dashboard->name}
				      		{/foreach}
				      		<option value="">-- {$translate->say('dashboard.add_dashboard')|lower} --
				      	</select>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="{devblocks_url}c=tickets&a=addView{/devblocks_url}">{$translate->say('dashboard.add_view')|lower}</a>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="#">{$translate->say('common.customize')|lower}</a>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="#">{$translate->say('common.remove')|lower}</a>
					</td>
				</tr>
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;">
						 <a href="{devblocks_url}c=tickets{/devblocks_url}">{$translate->say('common.refresh')|lower}</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
