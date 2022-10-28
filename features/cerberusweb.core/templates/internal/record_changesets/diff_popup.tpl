{$uniqid = uniqid('editor')}

<div style="display:flex;">
    <div style="flex:1 1 200px;margin-right:2px;">
        <div class="cerb-code-editor-toolbar">
            <button type="button" data-cerb-toolbar-button-refresh title="{'common.refresh'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-refresh"></span></button>
        </div>
        <div style="max-height:35em;overflow-y:auto;">
            <table class="worklistBody" style="width:100%;" cellpadding="0" cellspacing="0">
                {include file="devblocks:cerberusweb.core::internal/record_changesets/changesets.tpl" changesets=$changesets}
            </table>
        </div>
    </div>
    <div style="flex:2 2 100%;">
        <div class="cerb-code-editor-toolbar">
            <div style="height:26px;width:1px;display:inline-block;"></div>
            <button type="button" data-cerb-toolbar-button-next-change title="Next change" style="float:right;"><span class="glyphicons glyphicons-step-forward"></span></button>
            <button type="button" data-cerb-toolbar-button-prev-change title="Previous change" style="float:right;"><span class="glyphicons glyphicons-step-backward"></span></button>
        </div>
        <div style="position:relative;width:100%;height:35em;">
            <div id="{$uniqid}"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
Devblocks.loadResources({
    'js': [
        '/resource/cerberusweb.core/js/ace-diff/ace-diff.js'
    ]
}, function () {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);

    $popup.dialog('option', 'title', '{'common.change_history'|devblocks_translate|capitalize}');

    const $table = $popup.find('.worklistBody').disableSelection();

    const $spinner = Devblocks.getSpinner()
        .css('max-width', '16px')
        .css('position', 'absolute')
        .css('right', '0')
        .css('top', '0')
        .css('z-index', '100000')
    ;

    let diff_options = {
        element: '#{$uniqid}',
        theme: 'ace/theme/cerb-2022011201',
        mode: "ace/mode/cerb_kata",
        left: {
            content: {$left_content|json_encode nofilter},
            editable: false,
            copyLinkEnabled: false
        },
        right: {
            content: '',
            editable: true,
            copyLinkEnabled: false
        },
    };

    let differ = new AceDiff(diff_options);

    differ.editors.left.ace.setOption('highlightActiveLine', false);
    differ.editors.right.ace.setOption('highlightActiveLine', false);

    differ.editors.left.ace.session.setNewLineMode('unix');
    differ.editors.right.ace.session.setNewLineMode('unix');

    let onRefresh = function () {
        let formData = new FormData();
        formData.set('c', 'internal');
        formData.set('a', 'invoke');
        formData.set('module', 'records');
        formData.set('action', 'refreshChangesets');
        formData.set('record_type', '{$record_type}');
        formData.set('record_id', '{$record_id}');
        formData.set('record_key', '{$record_key}');

        $table.html(Devblocks.getSpinner());

        genericAjaxPost(formData, null, null, function (json) {
            if ('object' != typeof json)
                return;

            if (json.hasOwnProperty('html')) {
                $table.hide().html(json.html).fadeIn();
            }

            if (json.hasOwnProperty('{$record_key}')) {
                differ.editors.left.ace.setValue(json.{$record_key});
                differ.editors.left.ace.clearSelection();
            }
        });
    }

    $table.on('click', function (e) {
        e.stopPropagation();

        const $target = $(e.target);
        const $tr = $target.parentsUntil('tbody', 'tr');

        if (!$tr.is('[data-cerb-changeset-id]'))
            return;

        const changeset_id = $tr.attr('data-cerb-changeset-id');

        $table.find('tr.selected').removeClass('selected');

        $tr.addClass('selected');

        let formData = new FormData();
        formData.set('c', 'internal');
        formData.set('a', 'invoke');
        formData.set('module', 'records');
        formData.set('action', 'getChangesetJson');
        formData.set('changeset_id', changeset_id);

        $spinner.appendTo($tr.find('td'));
        $div.fadeTo('fast', 0.2, function () {
            genericAjaxPost(formData, null, null, function (json) {
                if ('object' == typeof json && json.hasOwnProperty('{$record_key}')) {
                    differ.editors.left.ace.setValue(json.{$record_key});
                    differ.editors.left.ace.clearSelection();
                }

                $spinner.detach();
                $div.fadeTo('slow', 1.0);
            });
        });
    });

    let diff_step = 0;

    // https://github.com/ace-diff/ace-diff/issues/48
    let onStepToDiff = function () {
        let $button = $(this);

        let delta = $button.is('[data-cerb-toolbar-button-prev-change]') ? -1 : 1;

        diff_step += delta;

        if (diff_step < 0) {
            diff_step = differ.diffs.length - 1;
        } else if (diff_step > differ.diffs.length - 1) {
            diff_step = 0;
        }

        if (!differ.diffs[diff_step])
            return;

        let left_row = differ.diffs[diff_step].leftStartLine;
        let right_row = differ.diffs[diff_step].rightStartLine;

        if (left_row > 5) {
            left_row -= 5;
        }

        if (right_row > 5) {
            right_row -= 5;
        }

        differ.getEditors().left.scrollToLine(left_row);
        differ.getEditors().right.scrollToLine(right_row);
    };

    $popup.find('[data-cerb-toolbar-button-refresh]').on('click', onRefresh);
    $popup.find('[data-cerb-toolbar-button-prev-change]').on('click', onStepToDiff);
    $popup.find('[data-cerb-toolbar-button-next-change]').on('click', onStepToDiff);

    $popup.triggerHandler($.Event('cerb-diff-editor-ready', { differ: differ }));
});
</script>