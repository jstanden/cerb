<div class="cerb-ui-panel cerb-ui-panel--spaced" id="page{$page->id}Config" style="margin-top:10px;">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.options'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle">
			<input type="checkbox" name="params[tab_sorting]" value="1" id="pageTabSorting{$page->id}" {if array_key_exists('tab_sorting', $page->extension_params) && $page->extension_params['tab_sorting']}checked="checked"{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>
		<label for="pageTabSorting{$page->id}">Allow workers to personalize the order of tabs on this page</label>
	</div>
</div>
