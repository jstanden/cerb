<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="wgm.google_cse.config.tab">
<input type="hidden" name="id" value="{$engine->id}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($engine->id)}
			<h2>{'wgm.google_cse.cfg.engine.add'|devblocks_translate|capitalize}</h2>
			{else}
			<h2>{'wgm.google_cse.cfg.engine.edit'|devblocks_translate:$engine->name|escape}</h2>
			{/if}
		</td>
	</tr>

	<tr>
		<td width="100%" valign="top" colspan="2">
			<b>{'wgm.google_cse.cfg.engine.name'|devblocks_translate}:</b><br>
			<input type="text" name="name" value="{$engine->name|escape}" size="32"><br>
			<i>{'wgm.google_cse.cfg.engine.name.example'|devblocks_translate}</i><br>
			<br>
			
			<b>{'wgm.google_cse.cfg.engine.url'|devblocks_translate}:</b> &nbsp; <a href="#" target="_blank" style="font-style:italic;">{'wgm.google_cse.cfg.engine.url.whats_this'|devblocks_translate}</a><br>
			<input type="text" name="url" value="{$engine->url|escape}" size="64"><br>
			<i>{'wgm.google_cse.cfg.engine.url.example'|devblocks_translate}</i><br>
			<br>
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			<input type="hidden" name="do_delete" value="0">
			<div id="deleteEngine" style="display:none;">
				<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:10px;padding:5px;">
					<h3>{'wgm.google_cse.cfg.engine.delete.confirm'|devblocks_translate}</h3>
					<button type="button" onclick="this.form.do_delete.value='1';this.form.submit();">{'common.remove'|devblocks_translate|capitalize}</button>
					<button type="button" onclick="this.form.do_delete.value='0';toggleDiv('deleteEngine','none');">{'common.cancel'|devblocks_translate|capitalize}</button>
				</div>
				<br>
			</div>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($engine->id)}<button type="button" onclick="toggleDiv('deleteEngine','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>
