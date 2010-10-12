<table cellpadding="0" cellspacing="0" border="0" class="sidebar" id="history_sidebar">
	<tr>
		<th>{$translate->_('common.search')|capitalize}</th>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}c=history&a=search{/devblocks_url}" method="POST" style="padding-bottom:5px;">
				<input type="text" name="q" value="{$q|escape}" style="width:100%;"><br>
				<select name="scope">
					<option value="all" {if $scope=='all'}selected="selected"{/if}>all words</option>
					<option value="any" {if $scope=='any'}selected="selected"{/if}>any words</option>
					<option value="phrase" {if $scope=='phrase'}selected="selected"{/if}>phrase</option>
				</select>
				<button type="submit">{'common.search'|devblocks_translate|lower}</button>
			</form>
		</td>
	</tr>
</table>
<br>
