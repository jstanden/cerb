<div>
    <div class="cerb-code-editor-toolbar">
        <button type="button"><span class="glyphicons glyphicons-refresh"></span></button>
    </div>

    <div data-cerb-automation-editor--log></div>
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
    var $script = $('#{$script_uid}');
    var $popup = genericAjaxPopupFind($script);
    var $panel = $script.closest('.ui-tabs-panel');
    var $toolbar = $panel.find('.cerb-code-editor-toolbar');
    
    var $log = $panel.find('[data-cerb-automation-editor--log]');

    $log.on('cerb-sheet--page-changed', function(e) {
        e.stopPropagation();

        var formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'automation');
        formData.set('action', 'editorLogRefresh');
        formData.set('automation_name', $popup.find('input[name="name"]').val());
        formData.set('page', e.page);

        genericAjaxPost(formData, $log);
    });

    $toolbar.find('button').on('click', function (e) {
        e.stopPropagation();
        
        var formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'automation');
        formData.set('action', 'editorLogRefresh');
        formData.set('automation_name', $popup.find('input[name="name"]').val());
        
        genericAjaxPost(formData, $log);
    }).click();
    
});
</script>