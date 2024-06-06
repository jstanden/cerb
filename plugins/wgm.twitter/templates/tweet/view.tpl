{$view_context = Context_TwitterMessage::ID}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if array_key_exists('header_color', $view->options) && $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a data-cerb-worklist-icon-search title="{'common.search'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-search"></span></a>
			<a data-cerb-worklist-icon-customize title="{'common.customize'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a data-cerb-worklist-icon-subtotals title="{'common.subtotals'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a data-cerb-worklist-icon-export title="{'common.export'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a data-cerb-worklist-icon-copy title="{'common.copy'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a data-cerb-worklist-icon-refresh title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="twitter_message">
<input type="hidden" name="action" value="viewMarkClosed">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:50px;"></th>
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
	</tr>
	</thead>

	{* Column Data *}
	
	{$url_writer = DevblocksPlatform::services()->url()}
	
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="t_user_profile_image_url" rowspan="2" nowrap="nowrap" align="center" style="padding:5px 0px;">
				<img src="{$url_writer->writeImageProxyUrl($result.t_user_profile_image_url)}" style="margin-right:5px;border-radius:5px;" width="48" height="48">
				<input type="checkbox" name="row_id[]" value="{$result.t_id}" style="display:none;">
			</td>
			<td data-column="label" colspan="{$smarty.foreach.headers.total}" style="font-size:100%;padding:5px 0px;">
				{if $result.t_is_closed}<span class="glyphicons glyphicons-circle-ok" style="font-size:16px;color:rgb(80,80,80);"></span>{/if}
				<a class="subject" title="@{$result.t_user_name}" href="http://twitter.com/{{$result.t_user_screen_name}}/status/{$result.t_twitter_id}" target="_blank" rel="noopener noreferrer">@{$result.t_user_screen_name}</a> 
				{$result.t_content|escape|devblocks_hyperlinks nofilter}
				
				<a href="{devblocks_url}c=profiles&type=twitter_message&id={$result.t_id}{/devblocks_url}" class="subject">{$result.t_name}</a>
				<button type="button" class="peek" onclick="genericAjaxPopup('peek','c=profiles&a=invoke&module=twitter_message&action=showPeekPopup&id={$result.t_id}&view_id={$view->id}',null,false,'50%');"><span class="glyphicons glyphicons-new-window-alt"></span></button>
			</td>
		</tr>
		
		<tr class="{$tableRowClass}">
		
		{foreach from=$view->view_columns item=column name=columns}
			{if DevblocksPlatform::strStartsWith($column, "cf_")}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="t_created_date"}
				<td data-column="{$column}" title="{$result.$column|devblocks_date}" nowrap="nowrap">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_connected_account_id"}
				<td data-column="{$column}">
					{$conn_acct_id = $result.$column}
					{$conn_acct = $connected_accounts.$conn_acct_id}
					{if $conn_acct}
						<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$conn_acct->id}">{$conn_acct->name}</a>
					{/if}
				</td>
			{elseif $column=="t_user_screen_name"}
				<td data-column="{$column}">
					@{$result.$column}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total >= 0}
<div style="padding-top:5px;">
	{include file="devblocks:cerberusweb.core::internal/views/view_paging.tpl" view=$view}

	<div style="float:left;" id="{$view->id}_actions">
		{$view_toolbar = $view->getToolbar()}
		{include file="devblocks:cerberusweb.core::internal/views/view_toolbar.tpl" view_toolbar=$view_toolbar}
		<button type="button" class="action-close" onclick="$frm=$(this).closest('form');$frm.find('input:hidden[name=action]').val('viewMarkClosed');genericAjaxPost($frm,'view{$view->id}',null);"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.close'|devblocks_translate|lower}</button>
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		var $view_actions = $('#{$view->id}_actions');
		var hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			case 99: // (c) close
				var $btn = $view_actions.find('button.action-close');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
			
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