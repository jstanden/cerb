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
	
		{/if}
</form>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $input = $div.find('input:text');
	var $popup = $input.closest('.ui-dialog');
	var isInPopup = ($popup.length > 0);
		
	var autocompleteFields = ['OR ','AND '];
	
	{if !empty($search_fields)}
	{foreach from=$search_fields key=field_key item=field}
	autocompleteFields.push('{$field_key}:');
	{/foreach}
	{/if}
	
	{$placeholder_labels = $view->getPlaceholderLabels()}
	{if !empty($placeholder_labels)}
	{foreach from=$placeholder_labels key=k item=v}
	autocompleteFields.push('{literal}{{{/literal}{$k}{literal}}}{/literal}');
	{/foreach}
	{/if}
	
	function split( val ) {
		return val.split(' ');
	}
	
	function extractLast( term ) {
		return split(term).pop();
	}	
	
	$input.autocomplete({
		autoFocus: false,
		minLength: 0,
		delay: 0,
		source: function(req, res) {
			var q = '';
			
			if(0 == req.term.length) {
				q = '';
				
			} else {
				var pos = $input.caret('pos');
				
				for(idx = pos; idx >= 0; idx--) {
					var c = req.term[idx-1];
					
					if(idx == 0 || c == ' ' || c == '(') {
						var offset = (c == ' ' || c == '(') ? 1 : 0;
						var q = req.term.substring(idx + offset, pos);
						break;
					}
				}
			}
			
			res($.ui.autocomplete.filter(autocompleteFields, q));
		},
		focus: function() {
			return false;
		},
		select: function(event, ui) {
			var pos = $input.caret('pos');
			var val = $input.val();
			
			if(0 == pos) {
				this.value = ui.item.value;
				return false;
			}
			
			for(idx = pos - 1; idx >= 0; idx--) {
				var c = val[idx];
				
				if(idx == 0 || c == ' ' || c == '(') {
					var offset = (c == ' ' || c == '(') ? 1 : 0;
					this.value = val.substring(0, idx + offset) + ui.item.value + val.substr(pos);
					$input.caret('pos', idx + ui.item.value.length + 1);
					return false;
				}
			}
			
			return false;
		}
	});

	var $menu_trigger = $div.find('a.cerb-quick-search-menu-trigger').click(function() {
		var $menu = $input.autocomplete('widget');
		
		if($menu.is(':visible')) {
			$input.autocomplete('close');
		} else {
			$input.autocomplete('search', '');
		}
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