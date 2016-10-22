{$form_id = uniqid()}
<form action="javascript:;" method="post" id="{$form_id}" onsubmit="return false;">

{foreach from=$services item=service}
<div class="cerb-service-provider" data-extension="{$service->id}" style="cursor:pointer;display:inline-block;margin:5px;padding:10px;font-size:120%;border-radius:5px;border:1px solid rgb(150,150,150);background-color:rgb(240,240,240);">{$service->name}</div>
{/foreach}

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Connect to an account:");
		
		$popup.find('div.cerb-service-provider').each(function() {
			var $provider = $(this);
			var extension = $provider.attr('data-extension');
			
			if(null == extension)
				return;
			
			$provider.click(function() {
				window.open('{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=connected_account&action=auth&extension_id=' + extension + '&view_id={$view_id}{/devblocks_url}', 'auth', 'width=1024,height=768');
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>
