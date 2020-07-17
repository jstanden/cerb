<div id="{$layer}">
    <form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-form-builder" style="{if 'inline' === $interaction_style}padding-left:10px;{/if}" onsubmit="return false;">
        <input type="hidden" name="session_id" value="{$session_id}">
        <div class="cerb-form-data"></div>
    </form>
</div>

<script type="text/javascript">
$(function() {
    var $layer = $('#{$layer}');
    var $form = $layer.find('.cerb-form-builder');
    var $data = $form.find('.cerb-form-data');

    var $spinner = Devblocks.getSpinner();

    $form.on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        $form.triggerHandler('cerb-form-builder-submit');
        return false;
    });

    $form.on('cerb-form-builder-submit', function(e) {
        e.stopPropagation();

        $spinner.insertAfter($data.hide());

        var formData = new FormData($form[0]);
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'bot');
        formData.set('action', 'sendMessage');

        genericAjaxPost(formData, null, null, function(html) {
            $spinner.detach();
            $data.html(html).fadeIn();
            $data.find('input:text:first').focus();
        });
    });

    $form.on('cerb-form-builder-reset', function(e) {
        e.stopPropagation();
        $form.trigger($.Event('cerb-interaction-reset'));
    });

    $form.on('cerb-form-builder-end', function(e) {
        e.stopPropagation();
        $form.trigger($.Event('cerb-interaction-done'));
    });

    $form.triggerHandler('cerb-form-builder-submit');
});
</script>