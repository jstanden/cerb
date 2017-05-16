<div align="left">
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
	<input type="hidden" name="c" value="display">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="id" value="{$dict->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
		<b>History for:</b> 
		<label><input type="radio" name="scope" value="email" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if empty($scope) || 'email'==$scope}checked="checked"{/if}> {'common.participants'|devblocks_translate|capitalize}</label>
		{if !empty($dict->org_id)}<label><input type="radio" name="scope" value="org" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if 'org'==$scope}checked="checked"{/if}> {'common.organization'|devblocks_translate|capitalize}</label>{/if}
		<label><input type="radio" name="scope" value="domain" onclick="this.form.a.value='doTicketHistoryScope';this.form.submit();" {if 'domain'==$scope}checked="checked"{/if}> Sender Domain</label>
	</form>
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$('#view{$view->id}').bind('view_refresh', function() {
	var $view = $('#view{$view->id}');
	var total = $view.data('total');
	
	if(null != total) {
		var $tabs = $view.closest('div.ui-tabs');
		var $tab = $tabs.find('> ul.ui-tabs-nav > li.ui-tabs-active');
		var $badge = $tab.find('> a > div.tab-badge');
		
		if(0 == $badge.length)
			return;
		
		$badge.text(total);
	}
});
</script>