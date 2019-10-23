<div id="page{$page->id}Config" style="margin-top:10px;">
	<fieldset id="page{$widget->id}Columns" class="peek">
		<legend>
			{'common.workspace.page.config.tab_type'|devblocks_translate|capitalize}:
		</legend>
		
		<label>
			<input type="radio" name="params[tab_style]" value="" {if !$page->extension_params.tab_style}checked="checked"{/if}> {'common.workspace.page.config.tab.as_tabs'|devblocks_translate}
		</label>
		
		<label>
			<input type="radio" name="params[tab_style]" value="menu" {if 'menu' == $page->extension_params.tab_style}checked="checked"{/if}> {'common.workspace.page.config.as_dropdown'|devblocks_translate}
		</label>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#page{$page->id}Config');
	var $frm = $config.closest('form');
});
</script>
