{$uniqid = uniqid()}
{if $view instanceof IAbstractView_QuickSearch}
{$menu = $view->getQuickSearchMenu()}

<form action="javascript:;" method="post" id="{$uniqid}" class="quick-search">
	<input type="hidden" name="c" value="search">
	<input type="hidden" name="a" value="ajaxQuickSearch">
	<input type="hidden" name="view_id" value="{$view->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div style="border:1px solid rgb(200,200,200);border-radius:10px;display:inline-block;">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%" valign="top">
					<input type="text" name="query" class="input_search cerb-input-quicksearch" style="border:0;width:100%;" size="16" value="{$view->getParamsQuery()}" autocomplete="off" spellcheck="false">
				</td>
				<td width="0%" nowrap="nowrap" valign="top">
					<a href="javascript:;" class="cerb-quick-search-menu-trigger" style="position:relative;top:5px;padding:0px 10px;"><span class="glyphicons glyphicons-chevron-down" style="margin:2px 0px 0px 2px;"></span></a>
				</td>
			</tr>
		</table>
	</div>
	
	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data->children) && !empty($data->children)}
				<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
					{if $data->key}
						<div style="font-weight:bold;">{$data->l}</div>
					{else}
						<div>{$idx}</div>
					{/if}
					<ul>
						{tree keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif $data->type == 'search' && $data->params}
				<li data-token="{$data->key}" data-label="{$data->label}" data-type="search" data-context="{$data->params.context}" data-query="{$data->params.query}" data-query-required="{$data->params.query_required}"><div style="font-weight:bold;">{$data->l}</div></li>
			{elseif $data->type == 'chooser' && $data->params}
				<li data-token="{$data->key}" data-label="{$data->label}" data-type="chooser" data-context="{$data->params.context}" data-query="{$data->params.query}" data-query-required="{$data->params.query_required}"><div style="font-weight:bold;">{$data->l}</div></li>
			{elseif $data->key}
				<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l}</div></li>
			{/if}
		{/foreach}
	{/function}
	
	<ul class="cerb-menu cerb-float" style="display:none;">
	{tree keys=$menu}
	</ul>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$uniqid}');
	var $input = $frm.find('input:text');
	var $popup = $input.closest('.ui-dialog');
	var isInPopup = ($popup.length > 0);
	var $menu = $frm.find('ul.cerb-menu').menu().zIndex($popup.zIndex()+1);
	
	var $menu_trigger = $frm.find('a.cerb-quick-search-menu-trigger').click(function() {
		$menu
			.toggle()
			.position({ my: "right top", at: "right bottom", of: $menu_trigger, collision: "fit" })
			;
	});
	
	$menu.find('li').on('click', function(e) {
		e.stopPropagation();

		var key = $(this).attr('data-token');
		var type = $(this).attr('data-type');
		
		if(key.length == 0)
			return;
		
		if(type == 'chooser') {
			var $context = $(this).attr('data-context');
			var $q = $(this).attr('data-query');
			var width = $(window).width()-100;
			var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + encodeURIComponent($context),null,true,width);
			
			$chooser.one('chooser_save', function(event) {
				var val = key + '[' + event.values.join(',') + ']';
				
				if(key.substr(-1) != ')')
					val += ' ';
				
				$input.insertAtCursor(val).focus();
			});
			
		} else if(type == 'search') {
			var $context = $(this).attr('data-context');
			var $q = $(this).attr('data-query');
			var width = $(window).width()-100;
			var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context=' + encodeURIComponent($context) + '&q=' + encodeURIComponent($q),null,true,width);
			
			$chooser.on('chooser_save',function(event) {
				if(null != event.worklist_model) {
					var val = key + '(' + event.worklist_quicksearch + ')';
					
					if(key.substr(-1) != ')')
						val += ' ';
					
					$input.insertAtCursor(val).focus();
				}
			});
			
		} else {
			var val = key;
			
			if(key.substr(-1) != ':')
				val += ' ';
			
			$input.insertAtCursor(val).focus();
		}
	});
	
	$frm.submit(function() {
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