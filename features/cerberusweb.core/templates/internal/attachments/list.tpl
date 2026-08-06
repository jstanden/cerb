{if empty($attachments)}{$attachments = DAO_Attachment::getByContextIds($context, $context_id)}{/if}
{$attach_uniqid = uniqid()}

{if $attachments}
<ul id="{$attach_uniqid}" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-1 cerb-u-my-2 cerb-u-py-1 cerb-u-px-0" style="list-style:none;">
	{foreach from=$attachments item=attachment}
	<li>
		<span class="cerb-ui-pill" style="white-space:normal;word-break:break-word;">
			<span class="cerb-icons cerb-icon-paperclip"></span>
			<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}" data-profile-url="{devblocks_url}c=files&id={$attachment->id}&name={$attachment->name|devblocks_permalink}{/devblocks_url}"{if !empty($attachment->mime_type)} title="{$attachment->mime_type}"{/if}>
				<b>{$attachment->name}</b>
				<span class="cerb-u-fgg-7">({$attachment->storage_size|devblocks_prettybytes})</span>
			</a>
			<a class="cerb-menu-trigger"><span class="cerb-icons cerb-icon-chevron-down"></span></a>
		</span>
	</li>
	{/foreach}
</ul>
<ul class="cerb-menu" style="display:none;position:absolute;">
	<li data-option="download"><div><b>Download</b></div></li>
	<li data-option="browser"><div><b>Open in browser</b></div></li>
</ul>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $attachments = $('#{$attach_uniqid}');
	let $target = null;

	const menuEl = $attachments.next('ul.cerb-menu')[0];
	const menu = (menuEl && window.CerbUI && CerbUI.Menu) ? new CerbUI.Menu(menuEl, {
		onSelect: function(li, src) {
			if(!$target)
				return;

			let url = null;

			switch(src.getAttribute('data-option')) {
				case 'card':
					$target.find('a').click();
					break;
				case 'browser':
					url = $target.find('a').attr('data-profile-url');
					window.open(url, '_blank', 'noopener');
					break;
				case 'download':
					url = $target.find('a').attr('data-profile-url') + '?download=';
					window.open(url);
					break;
			}

			$target = null;
		}
	}) : null;

	$attachments.find('a.cerb-menu-trigger')
		.hoverIntent({
			over: function() {
				$(this).click();
			},
			out: function(e) {
			}
		})
		.click(function(e) {
			e.preventDefault();
			e.stopPropagation();

			$target = $(this).closest('li');

			if(menu)
				menu.open(this);
		})
	;
});
</script>