<div style="padding:5px 10px;">
	(Default) Records are fulltext indexed in MyISAM tables in Cerb's existing MySQL database. 
	Tables are prefixed with <tt>fulltext_*</tt>. No special configuration is required.  This option 
	provides reasonable performance in most situations, but high volume environments should 
	consider using a specialized search engine like Sphinx instead.
</div>

{*
<div style="padding:5px 10px;">
	<label><input type="checkbox" name="reset" value="1"> Re-index these records.</label>
</div>
*}
