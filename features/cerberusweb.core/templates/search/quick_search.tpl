{$pref_token = DAO_WorkerPref::get($active_worker->id, "quicksearch_{get_class($view)|lower}", "")}

{capture "options"}
{foreach from=$view->getParamsAvailable() item=field key=token}
{if !empty($field->db_label) && !empty($field->type)}
<option value="{$token}"{if $pref_token eq $token}selected{/if}>{$field->db_label|capitalize}</option>
{/if}
{/foreach}
{/capture}

{$uniqid = uniqid()}

{if !empty($smarty.capture.options)}
	<form action="javascript:;" method="post" id="{$uniqid}">
	<input type="hidden" name="c" value="search">
	<input type="hidden" name="a" value="ajaxQuickSearch">
	<input type="hidden" name="view_id" value="{$view->id}">
	<input type="hidden" name="reset" value="{if !empty($reset)}1{else}0{/if}">
	<select name="field">
		{$smarty.capture.options nofilter}
	</select><input type="text" name="query" class="input_search" size="32" value="" autocomplete="off">
	</form>
{/if}

<script type="text/javascript">
$frm = $('#{$uniqid}');

$frm.find('select:first').change(function(e) {
	$(this).next('input:text[name=query]').focus();
});

$frm.find('input:text').keydown(function(e) {
	if(e.which == 13) {
		var $txt = $(this);
		
		genericAjaxPost('{$uniqid}','',null,function(json) {
			if(json.status == true) {
				{if !empty($return_url)}
					window.location.href = '{$return_url}';
				{else}
					$view_filters = $('#viewCustomFilters{$view->id}');
					
					if(0 != $view_filters.length) {
						$view_filters.html(json.html);
						$view_filters.trigger('view_refresh')
					}
				{/if}
			}
			
			$txt.select().focus();
		});
	}
});
</script>