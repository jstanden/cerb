{capture "options"}
{foreach from=$view->getParamsAvailable() item=field key=token}
{if !empty($field->db_label) && !empty($field->type)}
<option value="{$token}"{if $quick_search_type eq 'sender'}selected{/if}>{$field->db_label|capitalize}</option>
{/if}
{/foreach}
{/capture}

{$uniqid = uniqid()}

{if !empty($smarty.capture.options)}
	<form action="javascript:;" method="post" id="{$uniqid}">
	<input type="hidden" name="c" value="workspaces">
	<input type="hidden" name="a" value="ajaxQuickSearch">
	<input type="hidden" name="view_id" value="{$view_id}">
	<select name="field">
		{$smarty.capture.options nofilter}
	</select><input type="text" name="query" class="input_search" size="32" class="input_search" autocomplete="off">
	</form>
{/if}

<script type="text/javascript">
$frm = $('#{$uniqid}');

$frm.find('select:first').change(function(e) {
	$(this).next('input:text[name=query]').val('').focus();
});

$frm.find('input:text').keydown(function(e) {
	if(e.which == 13) {
		var $txt = $(this);
		
		genericAjaxPost('{$uniqid}','',null,function(json) {
			if(json.status == true) {
				$view_filters = $('#viewCustomFilters{$view_id}');
				
				if(0 != $view_filters.length) {
					$view_filters.html(json.html);
					$view_filters.trigger('view_refresh')
				}
			}
			
			$txt.select().focus();
		});
	}
});
</script>