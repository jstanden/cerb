{if empty($attachments)}{$attachments = DAO_Attachment::getByContextIds($context, $context_id)}{/if}
{$attach_uniqid = uniqid()}

{if $attachments}
<fieldset class="properties" style="padding:5px 0;border:0;">
	<ul id="{$attach_uniqid}" class="bubbles" style="display:block;">
		{foreach from=$attachments item=attachment}
		<li>
			<span class="glyphicons glyphicons-paperclip" style="vertical-align:baseline;"></span>
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}" data-profile-url="{devblocks_url}c=files&id={$attachment->id}&name={$attachment->name|devblocks_permalink}{/devblocks_url}">
				<b>{$attachment->name}</b>
				({$attachment->storage_size|devblocks_prettybytes}
				-
				{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
			</a>
			<a href="javascript:;" class="cerb-menu-trigger"><span class="glyphicons glyphicons-chevron-down" style="top:4px;"></span></a>
		</li>
		{/foreach}
	</ul>
	<ul class="cerb-menu" style="display:none;position:absolute;">
		<li data-option="download"><b>Download</b></li>
		<li data-option="browser"><b>Open in browser</b></li>
	</ul>
</fieldset>
{/if}

<script type="text/javascript">
$(function() {
	var $attachments = $('#{$attach_uniqid}');
	var $menu = $attachments.next('ul.cerb-menu').menu();
	var $target = null;
	
	$attachments.find('li > a.cerb-menu-trigger')
	.hoverIntent({
		over: function(e) {
			$(this).click();
		},
		out: function(e) {
		}
	})
	.click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		$target = $(this).closest('li');
		
		$menu.css('width', $target.width() + 'px');
		$menu.zIndex($target.zIndex()+1);
		$menu.show().position( { my: 'left top', at: 'left bottom', of: $target } );
	});
	
	$menu.find('li')
	.click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var $link = $(e.target);
		
		if(!$link.is('li'))
			return;
		
		switch($link.attr('data-option')) {
			case 'card':
				$target.find('a').click();
				break;
			case 'browser':
				var url = $target.find('a').attr('data-profile-url');
				window.open(url, '_blank', 'noopener');
				break;
			case 'download':
				var url = $target.find('a').attr('data-profile-url') + '?download=';
				window.open(url);
				break;
		}
		
		$menu.hide();
		$target = null;
	});
	
	$menu.hoverIntent({
		interval: 0,
		timeout: 250,
		over: function(e) {
			
		},
		out: function(e) {
			$menu.hide();
		}
	});
	
});
</script>