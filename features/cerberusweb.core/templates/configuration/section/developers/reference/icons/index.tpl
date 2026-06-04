<h2>Icon Reference (Built-in)</h2>

<div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
    <div style="position:relative;display:inline-block;">
        <span class="cerb-icons cerb-icon-search" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:120%;color:#888;pointer-events:none;"></span>
        <input type="search" id="cerb-icon-filter" placeholder="Filter icons…" autofocus
            style="width:300px;padding:5px 5px 5px 30px;">
    </div>
    <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;">
        <input type="checkbox" id="cerb-icon-hide-labels"> Hide labels
    </label>
</div>

<div id="cerb-icon-grid" style="column-width:200px;margin-bottom:20px;">
    {foreach from=$icons_cerb item=icon}
        <div data-icon-name="{$icon}" title="Click to copy" style="cursor:pointer;">
            <span class="cerb-icons cerb-icon-{$icon}" style="font-size:200%;margin:5px;"></span>
            <span class="cerb-icon-label">{$icon}</span>
        </div>
    {/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
    const $input = $('#cerb-icon-filter');
    const $grid = $('#cerb-icon-grid');
    const $items = $grid.find('[data-icon-name]');

    $input.on('input search', $.debounce(250, function() {
        const q = $input.val().trim().toLowerCase();
        $items.each(function() {
            const name = $(this).attr('data-icon-name');
            $(this).toggle(!q || name.indexOf(q) !== -1);
        });
    }));

    $items.on('click', function() {
        const name = $(this).attr('data-icon-name');
        const markup = '<span class="cerb-icons cerb-icon-' + name + '"></span>';
        navigator.clipboard.writeText(markup);
        Devblocks.createAlert('Copied icon to clipboard!');
    });

    $('#cerb-icon-hide-labels').on('change', function() {
        const hide = this.checked;
        $grid.find('.cerb-icon-label').toggle(!hide);
        $grid.css('column-width', hide ? '40px' : '200px');
    });
})();
</script>
