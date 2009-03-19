<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="wgm.google_cse.config.tab">
<input type="hidden" name="id" value="{$engine->id}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($engine->id)}
			<h2>Add Search Engine</h2>
			{else}
			<h2>Modify '{$engine->name}'</h2>
			{/if}
		</td>
	</tr>

	<tr>
		<td width="100%" valign="top" colspan="2">
			<b>Name:</b><br>
			<input type="text" name="name" value="{$engine->name|escape}" size="32"><br>
			<i>example: Cerb4 Resources</i><br>
			<br>
			
			<b>Google Custom Search Engine URL:</b> &nbsp; <a href="#" target="_blank" style="font-style:italic;">what's this?</a><br>
			<input type="text" name="url" value="{$engine->url|escape}" size="64"><br>
			<i>example: http://www.google.com/coop/cse?cx=005735772598845974453:efhnjjsndd0</i><br>
			<br>
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			<input type="hidden" name="do_delete" value="0">
			<div id="deleteEngine" style="display:none;">
				<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:10px;padding:5px;">
					<h3>Delete Group</h3>
					<button type="button" onclick="this.form.do_delete.value='1';this.form.submit();">Delete</button>
					<button type="button" onclick="this.form.do_delete.value='0';toggleDiv('deleteEngine','none');">Cancel</button>
				</div>
				<br>
			</div>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($engine->id)}<button type="button" onclick="toggleDiv('deleteEngine','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>
