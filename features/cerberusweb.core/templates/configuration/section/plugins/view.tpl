{$app_version = DevblocksPlatform::strVersionToInt($smarty.const.APP_VERSION)}
{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="">
<input type="hidden" name="a" value="">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="width:106px;"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}no-sort{/if}">
			{if (!array_key_exists('disable_sorting', $view->options) || !$view->options.disable_sorting) && !empty($view_fields.$header->db_column)}
				<a data-cerb-worklist-sort="{$header}">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}

			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if array_key_exists('disable_sorting', $view->options) && $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
		<th class="no-sort" style="width:80px;text-align:center;" title="{'common.enabled'|devblocks_translate|capitalize}">{'common.enabled'|devblocks_translate|capitalize}</th>
	</tr>
	</thead>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}
	{$plugin = $plugins.{$result.c_id}}
	{$meets_requirements = true}
	{if !empty($plugin)}
		{$meets_requirements = $plugin->checkRequirements()}
	{/if}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody{if !$result.c_enabled} class="cerb-plugin-disabled"{/if}>
		<tr class="{$tableRowClass}">
			<td data-column="icon" rowspan="4" align="center">
				<div style="margin:0px 5px 5px 5px;position:relative;">
					{if !empty($plugin) && isset($plugin->manifest_cache.plugin_image) && !empty($plugin->manifest_cache.plugin_image)}
						<img src="{devblocks_url}c=resource&p={$plugin->id}&f={$plugin->manifest_cache.plugin_image}{/devblocks_url}?v={$cerb_app_build}" width="100" height="100" style="border:1px solid var(--cerb-color-background-contrast-200);">
					{else}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/plugin_code_gray.gif{/devblocks_url}?v={$cerb_app_build}" width="100" height="100" style="border:1px solid var(--cerb-color-background-contrast-200);">
					{/if}
				</div>
			</td>
			<td colspan="{$smarty.foreach.headers.total}"></td>
			<td data-column="*_enabled_toggle" rowspan="4" align="center" valign="middle">
				<label class="cerb-toggle-switch" title="{if !$meets_requirements}{'config.plugins.toggle.requirements'|devblocks_translate}{else}{'common.enabled'|devblocks_translate|capitalize}{/if}">
					<input type="checkbox" data-cerb-plugin-toggle data-plugin-id="{$result.c_id}" {if $result.c_enabled}checked="checked"{/if} {if !$meets_requirements && !$result.c_enabled}disabled="disabled"{/if}>
					<span class="cerb-toggle-slider"></span>
				</label>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				<div style="padding-bottom:2px;">
					<b class="subject" style="font-size:1.5em;">{$result.c_name}</b>
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}

			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="c_updated"}
				<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.c_updated|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="c_version"}
				<td data-column="{$column}">
					{if isset($plugin->version) && $plugin->version < $result.c_version}
						<div class="badge" style="font-weight:bold;">{DevblocksPlatform::intVersionToStr($result.$column)}</div>
					{else}
						{DevblocksPlatform::intVersionToStr($result.$column)}
					{/if}
				</td>
			{elseif $column=="c_link"}
				<td data-column="{$column}">
					<a href="{$result.$column}" target="_blank" rel="noopener noreferrer">{$result.$column}</a>
				</td>
			{elseif $column=="c_enabled"}
				<td data-column="{$column}">
					{if !$result.c_enabled}
						<a style="color:var(--cerb-color-error-text);text-decoration:none;font-weight:bold;">{'common.no'|devblocks_translate}</a>
					{else}
						<a style="color:var(--cerb-color-text);text-decoration:none;font-weight:normal;">{'common.yes'|devblocks_translate}</a>
					{/if}
				</td>
			{elseif $column=="c_author"}
				<td data-column="{$column}">
					{if !empty($column.c_link)}
						<a href="{$result.c_link}" target="_blank" rel="noopener noreferrer">{$result.$column}</a>
					{else}
						{$result.$column}
					{/if}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}" valign="top">
				<div style="padding:5px;">
					{$result.c_description}
				</div>

				{if !$meets_requirements && !empty($plugin)}
					{$errors = $plugin->getRequirementsErrors()}
					{if !empty($errors)}
					<div style="padding:5px;">
						<b>{'config.plugins.requirements_missing'|devblocks_translate}</b>
						<ul style="margin:0;">
						{foreach from=$errors item=error name=errors}
							<li>{$error}</li>
						{/foreach}
						</ul>
					</div>
					{/if}
				{/if}
			</td>
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total >= 0}
<div style="padding-top:5px;">
	{include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

	<div style="float:left;" id="{$view->id}_actions">
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#viewForm{$view->id}');

	$frm.find('table.worklistBody tbody').off('click').off('mouseenter mouseleave');

	$frm.find('[data-cerb-plugin-toggle]').on('change', function(e) {
		e.stopPropagation();

		let $cb = $(this);
		let plugin_id = $cb.attr('data-plugin-id');
		let enabled = $cb.prop('checked') ? 1 : 0;

		let formData = new FormData();
		formData.set('c', 'config');
		formData.set('a', 'invoke');
		formData.set('module', 'plugins');
		formData.set('action', 'toggleEnabled');
		formData.set('plugin_id', plugin_id);
		formData.set('enabled', enabled);
		formData.set('_csrf_token', '{$session.csrf_token}');

		$cb.prop('disabled', true);

		genericAjaxPost(formData, '', '', function(json) {
			$cb.prop('disabled', false);

			if(!json || true !== json.status) {
				// Revert the switch to its prior state
				$cb.prop('checked', 0 === enabled);

				let msg = (json && json.errors && json.errors.length)
					? json.errors.join("\n")
					: '{'config.plugins.toggle.failed'|devblocks_translate|escape:'javascript' nofilter}';
				Devblocks.createAlertError(msg);
			} else {
				// Grey out the plugin art when disabled
				$cb.closest('tbody').toggleClass('cerb-plugin-disabled', 0 === enabled);
			}
		});
	});

	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		let hotkey_activated = true;

		switch(event.keypress_event.which) {
			default:
				hotkey_activated = false;
				break;
		}

		if(hotkey_activated)
			event.preventDefault();
	});
	{/if}
});
</script>