<style>
#cerbLoginMotdForm {
    h1 {
        font-size: 2em;
    }

    h1, h2, h3, h4, h5, h6 {
        margin: 0.5em 0;
        color: var(--cerb-color-text);
    }
}
</style>

<form action="{devblocks_url}c=login&a=motd{/devblocks_url}" method="post" id="cerbLoginMotdForm" onsubmit="return false;">
<input type="hidden" name="accept" value="1">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:900px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
    <div>
        {$motd_message nofilter}
    </div>

    <button type="button" class="submit" style="width:100%;">
        {if $motd_button}
            {$motd_button}
        {else}
            {'common.continue'|devblocks_translate|capitalize}
        {/if}
    </button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
   let $frm = $('#cerbLoginMotdForm');
   let $submit = $frm.find('.submit').attr('disabled', null);

    $submit.on('click', function(e) {
        e.stopPropagation();
        $frm.attr('onsubmit', null);
        $submit.attr('disabled', 'disabled');
        $frm.submit();
    });
});
</script>