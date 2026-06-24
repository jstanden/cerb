<div style="margin-top:5px;"></div>

<form action="#">
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Let's add some pages to your menu</div>
				<div class="cerb-ui-header--subtitle">
					<p>
						Pages allow you to build a completely personalized interface based on your needs.

						Your most frequently used pages can be added to the menu above by clicking on the <button type="button"><span class="cerb-icons cerb-icon-circle-plus" style="color:rgb(150,150,150);"></span></button> button.
					</p>

					{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_WORKSPACE_PAGE}.create")}
					<p>
						New pages can be added by clicking on the <span class="help callout-worklist" style="cursor:pointer;"><span class="cerb-icons cerb-icon-circle-plus"></span></span> icon in the <b>Pages</b> list below.
					</p>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
</form>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');
	$script.prev('form').find('.callout-worklist').on('click', function(e) {
		e.stopPropagation();
		$('#viewpages a[title=Add]').click();
	});
});
</script>

<div>
	<h2>Pages</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}