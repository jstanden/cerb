{$uniqid = uniqid()}
<div id="{$uniqid}">
	<fieldset class="cerb-facebook-account peek black">
		<legend>Facebook Account</legend>
		
		<button type="button" class="chooser-abstract" data-field-name="params[connected_account_id]" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-single="true" data-query="facebook"><span class="glyphicons glyphicons-search"></span></button>
		<ul class="bubbles chooser-container">
			{if $connected_account}
				<li><input type="hidden" name="params[connected_account_id]" value="{$connected_account->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$connected_account->id}">{$connected_account->name}</a></li>
			{/if}
		</ul>
		<br>
	</fieldset>
	
	{*if !$connected_account}display:none;{/if*}
	<fieldset class="peek black">
		<legend>Page</legend>
		
		<div class="cerb-facebook-pages">
			{if $params.page.name}
			Linked to <b>{$params.page.name}</b>
			{/if}
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $container = $('#{$uniqid}');
	var $fieldset_account = $container.find('fieldset.cerb-facebook-account');
	var $pages = $container.find('div.cerb-facebook-pages');
	
	$fieldset_account.find('button.chooser-abstract')
		.cerbChooserTrigger()
			// If the account changes, refresh
			.on('cerb-chooser-saved', function(e) {
				var $bubbles = $fieldset_account.find('ul.chooser-container');
				var $bubble = $bubbles.find('> li:first input:hidden');
				
				if($bubble.length == 0) {
					$pages.hide().html('');
					
				} else {
					var connected_account_id = $bubble.first().val();

					var formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'connected_service');
					formData.set('action', 'ajax');
					formData.set('ajax', 'getPagesFromAccount');
					formData.set('id', '{$service->extension_id}');
					formData.set('connected_account_id', connected_account_id);

					genericAjaxPost(formData, $pages, null, function() {
						$pages.fadeIn();
					});
				}
			})
		;
});
</script>