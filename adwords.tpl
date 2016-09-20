{include file="$template/includes/tablelist.tpl" tableName="ActiveHostingServicesList"}
<script type="text/javascript">
    jQuery(document).ready( function ()
    {
        var table = jQuery('#tableActiveHostingServicesList').removeClass('hidden').DataTable();

        table.draw();
        jQuery('#tableLoading').addClass('hidden');
    });

    // AJAX handling of coupon retrieving
</script>

<!-- shall we debug vars or not? 
<pre>
{$activeservices|@print_r}
<strong>...................................</strong>
<br>
<strong>Posted vars:</strong>
{$posted_value|@print_r}
<br>
Number of active services with issued coupon: <strong>{$num_of_services_with_coupon}</strong>
<br>
Number of total active services for specific user: <strong>{$num_of_active_services}</strong>
<br>
Is user allowed to fetch coupons: <strong>{if $is_user_allowed_to_fetch_coupons}Yes{else}No{/if}</strong>
<br>
Raw PDO message: <strong>{$pdo_message}</strong>
<br>
Flash message content: <strong>{$flash_message}</strong>
<br>
Number of available coupons: <strong>{$num_of_available_coupons}</strong>
<br>
UserID is <strong>{$userid}</strong>
</pre>
-->


<!-- is user logged in? -->
{if $loggedin}


	<!-- is client active? -->
	{if $clientsdetails.status eq 'Active'}

		{if $flash_message}
			<div class="alert alert-success text-center">
	        {$flash_message}
			</div>
		{/if}

		<div class="table-container clearfix">
		    <table id="tableActiveHostingServicesList" class="table table-list hidden">
		        <thead>
		            <tr>
		                <th>{$LANG.orderproduct}</th>
		                <th>Kupon</th>
		                <th>Preuzmi kupon</th>
		            </tr>
		        </thead>
		        <tbody>
		            {foreach from=$activeservices item=activeservice}
		                <tr>
	                    	<form method="post" action="{$smarty.server.PHP_SELF}">
	                    		<input type="hidden" name="action" value="fetch_coupon"></input>
	                    		<input type="hidden" name="hostingid" value="{$activeservice.HostingID}"></input>
	                    		<input type="hidden" name="userid" value="{$activeservice.UserID}"></input>
			                    <td class="text-center">{$activeservice.HostingDomain}</td>
			                    <td class="text-center">{$activeservice.Coupon}</span></td>
			                    <td class="text-center">
			                        {if $activeservice.Coupon eq 'Kupon nije izdan!'}
			                       		<input type="submit" class="btn btn-block btn-info" value="Preuzmi kupon!">
			                        {else}
			                       		<input type="submit" class="btn btn-block btn-info" disabled value="Kupon je preuzet!"></input>
		                       		{/if}
			                    </td>
		                    </form>
		                </tr>
		            {/foreach}
		        </tbody>
		    </table>
		    <div class="text-center" id="tableLoading">
		        <p><i class="fa fa-spinner fa-spin"></i> {$LANG.loading}</p>
		    </div>
		</div>		   
	{else}
		<p>AdWords kuponi su dostupni samo aktivnim korisnicima!</p>
	{/if}

{else}
<!-- Redirect user to log in form! -->
<p>Restricted for logged in users onyl!</p>
{/if}

{*{debug}*}
