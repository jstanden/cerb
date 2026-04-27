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

	<fieldset id="page{$widget->id}Columns" class="peek">
		<legend>{{'common.options'|devblocks_translate|capitalize}}:</legend>

		<label>
			<input type="checkbox" name="params[tab_sorting]" value="1" {if array_key_exists('tab_sorting', $page->extension_params) && $page->extension_params['tab_sorting']}checked="checked"{/if}> Allow workers to personalize the order of tabs on this page
		</label>
	</fieldset>
</div>