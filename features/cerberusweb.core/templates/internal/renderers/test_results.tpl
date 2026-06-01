{if !$success}
	<div class="error-box">
		<span data-cerb-link="remove_div" style="float:right;cursor:pointer;font-size:1.5em;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
		<h1 style="font-size:1.5em;font-weight:bold;">Error!</h1>
		<p>
			<pre class="emailbody" dir="auto">{$output|default:''|escape nofilter}</pre>
		</p>
	</div>
{else}
	<div class="help-box">
		<span data-cerb-link="remove_div" style="float:right;cursor:pointer;font-size:1.5em;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
		<h1 style="font-size:1.5em;font-weight:bold;">Success!</h1>
		<p>
			<pre class="emailbody" dir="auto">{$output|default:''|escape nofilter}</pre>
		</p>
	</div>
{/if}
{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');

	$script.prev('div').find('[data-cerb-link=remove_div]').on('click', function(e) {
		$(this).closest('div').remove();
	});
});
</script>