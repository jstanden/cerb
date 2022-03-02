<div id="page{$page->id}Config" style="margin-top:10px;">
	<fieldset id="page{$widget->id}Columns" class="peek">
		<legend>
			Display tabs as:
		</legend>
		
		<label>
			<input type="radio" name="params[tab_style]" value="" {if !array_key_exists('tab_style', $page->extension_params) || !$page->extension_params.tab_style}checked="checked"{/if}> Tab set (default)
		</label>
		
		<label>
			<input type="radio" name="params[tab_style]" value="menu" {if array_key_exists('tab_style', $page->extension_params) && 'menu' == $page->extension_params.tab_style}checked="checked"{/if}> Dropdown menu
		</label>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#page{$page->id}Config');
	var $frm = $config.closest('form');
});
</script>