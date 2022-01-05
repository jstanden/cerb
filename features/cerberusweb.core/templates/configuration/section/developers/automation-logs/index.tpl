<h2>Automation Logs</h2>

<div style="margin-bottom:25px;">
    <div class="cerb-code-editor-toolbar">
        <button type="button" data-cerb-button-check-all><span class="glyphicons glyphicons-check"></span></button>
        <button type="button" data-cerb-button-refresh><span class="glyphicons glyphicons-refresh"></span></button>
        <button type="button" data-cerb-button-delete><span class="glyphicons glyphicons-bin"></span></button>
    </div>

    <div data-cerb-automation-editor--log></div>
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
    $(function() {
        var $script = $('#{$script_uid}');
        var $panel = $script.prev('div');
        var $toolbar = $panel.find('.cerb-code-editor-toolbar');

        var $log = $panel.find('[data-cerb-automation-editor--log]');

        $log.on('cerb-sheet--page-changed', function(e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'automation_logs');
            formData.set('action', 'refresh');
            formData.set('page', e.page);

            genericAjaxPost(formData, $log);
        });

        $toolbar.find('button[data-cerb-button-check-all]').on('click', function (e) {
            e.stopPropagation();

            $log.find('input[name="_selection"]').each(function() {
                $(this).click();
            });
        });
        
        $toolbar.find('button[data-cerb-button-delete]').on('click', function (e) {
            e.stopPropagation();
            
            var formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'automation_logs');
            formData.set('action', 'delete');
            
            $log.find('input[name="_selection"]:checked').each(function() {
                formData.append('ids[]', $(this).val());
            });

            genericAjaxPost(formData, $log);
        });

        $toolbar.find('button[data-cerb-button-refresh]').on('click', function (e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'automation_logs');
            formData.set('action', 'refresh');

            genericAjaxPost(formData, $log);
        }).click();
    });
</script>