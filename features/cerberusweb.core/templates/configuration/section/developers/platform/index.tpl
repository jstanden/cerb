<h2>{'common.platform'|devblocks_translate|capitalize}</h2>

<div id="cerbConfigPlatform">
    <fieldset>
        <legend>{'common.automations'|devblocks_translate|capitalize}</legend>
        <button type="button" data-cerb-button="automations"><span class="glyphicons glyphicons-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
    </fieldset>
    
    <fieldset>
        <legend>{'common.cache'|devblocks_translate|capitalize}</legend>
        <button type="button" data-cerb-button="cache"><span class="glyphicons glyphicons-erase"></span> {'common.clear'|devblocks_translate|capitalize}</button>
    </fieldset>
    
    <fieldset>
        <legend>{'common.packages'|devblocks_translate|capitalize}</legend>
        <button type="button" data-cerb-button="packages"><span class="glyphicons glyphicons-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
    </fieldset>
    
    <fieldset>
        <legend>{'common.resources'|devblocks_translate|capitalize}</legend>
        <button type="button" data-cerb-button="resources"><span class="glyphicons glyphicons-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
    </fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
   let $assets = $('#cerbConfigPlatform');
   
   $assets.find('button[type=button]').on('click', function(e) {
       let action = $(e.target).attr('data-cerb-button');

       let formData = new FormData();
       formData.set('c', 'config');
       formData.set('a', 'invoke');
       formData.set('module', 'platform');
       
       if('automations' === action) {
           formData.set('action', 'reloadAutomations');
           genericAjaxPost(formData, '', '', function() {
               Devblocks.createAlert('Bundled automations have been reloaded.', 'note', 5000);
           });
       } else if('cache' === action) {
           formData.set('action', 'clearCache');
           genericAjaxPost(formData, '', '', function() {
               Devblocks.createAlert('Flushed the server-side cache.', 'note', 5000);
           });
       } else if('packages' === action) {
           formData.set('action', 'reloadPackages');
           genericAjaxPost(formData, '', '', function() {
               Devblocks.createAlert('Bundled packages have been reloaded.', 'note', 5000);
           });
       } else if('resources' === action) {
           formData.set('action', 'reloadResources');
           genericAjaxPost(formData, '', '', function() {
               Devblocks.createAlert('Bundled resources have been reloaded.', 'note', 5000);
           });
       }
   });
});
</script>