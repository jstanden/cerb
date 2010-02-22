<table cellpadding="0" cellspacing="0" border="0" class="sidebar" id="kb_sidebar">
	<tr>
		<th>{$translate->_('common.knowledgebase')|capitalize}</th>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}c=kb&a=search{/devblocks_url}" method="POST">
				<input type="text" name="q" value="{$q|escape}" style="width:100%;"><br>
				<button type="submit">{'common.search'|devblocks_translate|lower}</button>
			</form>
		</td>
	</tr>
</table>
<br>
