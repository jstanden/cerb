<style type="text/css">
@import url(http://www.google.com/cse/api/branding.css);
</style>
<div class="cse-branding-bottom" style="color:#000000;">
  <div class="cse-branding-form">
    <form action="http://www.google.com/cse" id="cse-search-box" target="_blank" onsubmit="this.cx.value=selectValue(document.getElementById('engine'));genericPanel.dialog('close');return true;">
      <div>
        <input type="hidden" name="cx" value="" />
        <input type="hidden" name="ie" value="UTF-8" />
        <input type="text" name="q" size="31" />
		<br>
	    <select id="engine">
	    	{foreach from=$engines item=engine}
		    <option value="{$engine->token}">{$engine->name}</option>
			{/foreach}
	    </select>
        <input type="submit" name="sa" value="{'common.search'|devblocks_translate|capitalize}" />
		<br>
      </div>
    </form>
  </div>
  <div class="cse-branding-logo">
    <img src="http://www.google.com/images/poweredby_transparent/poweredby_FFFFFF.gif" alt="Google" />
  </div>
  <div class="cse-branding-text">
    Custom Search
  </div>
</div>
