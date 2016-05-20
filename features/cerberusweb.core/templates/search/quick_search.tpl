{$uniqid = uniqid()}
{if $view instanceof IAbstractView_QuickSearch}
{$search_fields = $view->getQuickSearchFields()}

<form action="javascript:;" method="post" id="{$uniqid}" class="quick-search">
	<input type="hidden" name="c" value="search">
	<input type="hidden" name="a" value="ajaxQuickSearch">
	<input type="hidden" name="view_id" value="{$view->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div style="border:1px solid rgb(200,200,200);border-radius:10px;display:inline-block;">
		<input type="text" name="query" class="input_search cerb-input-quicksearch" style="border:0;" size="50" value="{$quick_search_query}" autocomplete="off" spellcheck="false">
		<a href="javascript:;" class="cerb-quick-search-menu-trigger" style="position:relative;top:5px;padding:0px 10px;"><span class="glyphicons glyphicons-chevron-down" style="margin:2px 0px 0px 2px;"></span></a>
	</div>
	
	<ul class="cerb-quick-search-menu" style="position:absolute;float:right;margin-right:10px;z-index:5;display:none;">
		{$placeholder_labels = $view->getPlaceholderLabels()}
		
		{if !empty($placeholder_labels)}
		<li field="">
			(placeholders)
			<ul style="width:200px;">
				{foreach from=$placeholder_labels item=v key=k}
				<li value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v.label}</li>
				{/foreach}
			</ul>
		</li>
		{/if}

		{capture name=sortable_fields}{*
		*}{if !empty($search_fields)}{*
		*}{foreach from=$search_fields key=field_key item=field}{*
		*}{if $field.is_sortable}
			<li field="">
				{$field_key}
				<ul style="width:200px;">
					<li value="sort:{$field_key}">ascending</li>
					<li value="sort:-{$field_key}">descending</li>
				</ul>
			</li>
		{/if}{*
		*}{/foreach}{*
		*}{/if}{*
		*}{/capture}
		
		{if $smarty.capture.sortable_fields}
		<li field="">
			(sort)
			<ul style="width:200px;">
				{$smarty.capture.sortable_fields nofilter}
			</ul>
		</li>
		{/if}
		
		{if !empty($search_fields)}
		{foreach from=$search_fields key=field_key item=field}
		<li field="{$field_key}">
			<b>{$field_key}</b>
			
			{if $field.examples}
				<ul style="width:200px;">
					{foreach from=$field.examples item=example}
					<li>{$example}</li>
					{/foreach}
				</ul>
			{else}
				{if $field.type == DevblocksSearchCriteria::TYPE_BOOL}
				<ul style="width:200px;">
					<li>yes</li>
					<li>no</li>
				</ul>
				{elseif $field.type == DevblocksSearchCriteria::TYPE_DATE}
				<ul style="width:200px;">
					<li>never</li>
					<li>"-1 day"</li>
					<li>"-2 weeks"</li>
					<li>"big bang to now"</li>
					<li>"yesterday to today"</li>
					<li>"last Monday to next Monday"</li>
					<li>"Jan 1 to Dec 31 23:59:59"</li>
				</ul>
				{elseif $field.type == DevblocksSearchCriteria::TYPE_FULLTEXT}
				<ul style="width:200px;">
					<li>word</li>
					<li>"any of these words"</li>
					<li>person@example.com</li>
				</ul>
				{elseif $field.type == DevblocksSearchCriteria::TYPE_NUMBER}
				<ul style="width:200px;">
					<li>1</li>
					<li>!=42</li>
					<li>&gt;0</li>
					<li>&gt;=5</li>
					<li>&lt;50</li>
					<li>&lt;=100</li>
					<li>1...10</li>
					<li>[1,2,3]</li>
					<li>![1,2,3]</li>
				</ul>
				{elseif $field.type == DevblocksSearchCriteria::TYPE_TEXT}
				<ul style="width:200px;">
					<li>word</li>
					<li>prefix*</li>
					<li>*wildcard*</li>
					<li>"a several word phrase"</li>
					<li>[this,that]</li>
					<li>![not,this,that]</li>
					<li>!(wildcard*)</li>
				</ul>
				{elseif $field.type == DevblocksSearchCriteria::TYPE_WORKER}
				<ul style="width:200px;">
					<li>me</li>
					<li>any</li>
					<li>none</li>
					<li>no</li>
					<li>[kina,milo,karl]</li>
				</ul>
				{/if}
			{/if}
		</li>
		{/foreach}
		{/if}
	</ul>

</form>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $input = $div.find('input:text');
	var $popup = $input.closest('.ui-dialog');
	var isInPopup = ($popup.length > 0);
	
	$input.keyup(function(e) {
		if(e.keyCode == 27) {
			if(!isInPopup) {
				$menu.hide();
				
			} else {
				if($menu.is(':visible')) {
					$menu.hide();
					
				} else {
					$popup.find('.devblocks-popup').dialog('close');
				}
			}
		}
	});
	
	var $menu = $div.find('ul.cerb-quick-search-menu')
		.menu({
			items: "> :not(.ui-widget-header)",
			select: function(event, ui) {
				var val = $input.val();
				
				if(undefined == ui.item.attr('field')) {
					var field_key = ui.item.parent().closest('li').attr('field');
					var field_value = '';

					if(ui.item.attr('value')) {
						field_value = ui.item.attr('value');
					} else {
						field_value = ui.item.text();
					}
					
					var insert_txt = (field_key ? (field_key + ':') : '') + field_value;
					
				} else {
					var field_key = ui.item.attr('field');
					var insert_txt = (field_key ? (field_key + ':') : '');
					
				}
				
				if(val.length > 0 && val.substr(-1) != " ")
					insert_txt = " " + insert_txt;
				
				if(insert_txt.length > 0) {
					$input.insertAtCursor(insert_txt).scrollLeft(2000);
					$menu.hide();
				}
			}
		})
		.css('width', $input.width())
		.hide()
		;
	
	var $menu_trigger = $div.find('a.cerb-quick-search-menu-trigger').click(function() {
		$menu.toggle();
		$input.insertAtCursor('').scrollLeft(2000);
	});
	
	$div.submit(function() {
		genericAjaxPost('{$uniqid}','',null,function(json) {
			if(json.status == true) {
				{if !empty($return_url)}
					window.location.href = '{$return_url}';
				{else}
					var $view_filters = $('#viewCustomFilters{$view->id}');
					
					if(0 != $view_filters.length) {
						$view_filters.html(json.html);
						$view_filters.trigger('view_refresh')
					}
				{/if}
			}
			
			$input.focus();
		});
	});
});
</script>
{/if}