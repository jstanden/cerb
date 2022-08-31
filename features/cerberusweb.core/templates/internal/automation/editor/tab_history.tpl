<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/ace-diff/ace-diff.js{/devblocks_url}"></script>

{if $pref_dark_mode}
<link href="{devblocks_url}c=resource&p=cerberusweb.core&f=js/ace-diff/ace-diff-dark.css{/devblocks_url}" rel="stylesheet">
{else}
<link href="{devblocks_url}c=resource&p=cerberusweb.core&f=js/ace-diff/ace-diff.css{/devblocks_url}" rel="stylesheet">
{/if}

{$uniqid = uniqid('editor')}

<div style="display:flex;">
    <div style="flex:1 1 200px;">
        <div class="cerb-code-editor-toolbar">
            <button type="button" data-cerb-toolbar-button-refresh title="{'common.refresh'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-refresh"></span></button>
        </div>
        <table class="worklistBody" style="width:100%;" cellpadding="0" cellspacing="0">
            {include file="devblocks:cerberusweb.core::internal/automation/editor/history/changesets.tpl" changesets=$changesets}
        </table>
    </div>
    <div style="flex:2 2 100%;">
        <div style="position:relative;width:100%;height:30em;">
            <div id="{$uniqid}"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);
    const $editor = $popup.find('[data-cerb-automation-editor-script] pre.ace_editor');
    const editor = ace.edit($editor.attr('id'));
    
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
            content: {$left_content nofilter},
            editable: false,
            copyLinkEnabled: false
        },
        right: {
            content: editor.getSession().getValue(),
            editable: false,
            copyLinkEnabled: false
        },
    };
    
    let $diff = new AceDiff(diff_options);

    $diff.editors.left.ace.setOption('highlightActiveLine', false);
    $diff.editors.right.ace.setOption('highlightActiveLine', false);
    
    $diff.editors.left.ace.session.setNewLineMode('unix');
    $diff.editors.right.ace.session.setNewLineMode('unix');

    let onUpdate = function() {
        try {
            $diff.editors.right.ace.setValue(editor.getSession().getValue());
            $diff.editors.right.ace.clearSelection();
        } catch(e) { }
    }

    editor.getSession().on('change', function() {
        onUpdate();
    });
    
    let onRefresh = function() {
        let formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'automation');
        formData.set('action', 'refreshChangesets');
        formData.set('automation_id', '{$automation_id|round}');

        $table.html(Devblocks.getSpinner());

        genericAjaxPost(formData, null, null, function(json) {
            if('object' != typeof json)
                return;

            if(json.hasOwnProperty('html')) {
                $table.hide().html(json.html).fadeIn();
            }

            if(json.hasOwnProperty('script')) {
                $diff.editors.left.ace.setValue(json.script);
                $diff.editors.left.ace.clearSelection();
            }
        });
    }
    
    $popup.on('popup_saved', function(e) {
        e.stopPropagation();
        onRefresh();
    });
    
    $table.on('click', function(e) {
        e.stopPropagation();
        
        const $target = $(e.target);
        const $tr = $target.parentsUntil('tbody', 'tr');
        
        if(!$tr.is('[data-cerb-changeset-id]'))
            return;
        
        const changeset_id = $tr.attr('data-cerb-changeset-id');
        
        $table.find('tr.selected').removeClass('selected');
        
        $tr.addClass('selected');

        let formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'automation');
        formData.set('action', 'getChangesetJson');
        formData.set('changeset_id', changeset_id);
        
        $spinner.appendTo($tr.find('td'));
        $div.fadeTo('fast', 0.2, function() {
            genericAjaxPost(formData, null, null, function(json) {
                if('object' == typeof json && json.hasOwnProperty('script')) {
                    $diff.editors.left.ace.setValue(json.script);
                    $diff.editors.left.ace.clearSelection();
                }

                $spinner.detach();
                $div.fadeTo('slow', 1.0);
            });
        });
    });
    
    $popup.find('[data-cerb-toolbar-button-refresh]').on('click', onRefresh);
});
</script>