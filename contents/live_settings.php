<?php 

$mySettings = get_option('feed_settings');
$isLoggedin = false;

if( isset($mySettings['live_password']) ){
	
	$success = $this->get_live_settings($mySettings['live_password']);

	if( isset($success['credsRatio']) && is_array($success) ){
		$isLoggedin = true;
	} else {
		unset($mySettings['live_password']);
		update_option('feed_settings', $mySettings);
	}
}

?>

<h2>Live settings</h2>
<p><?php _e("These are settings you can update without needing to regenerate the app.", "feed"); ?></p>
<p><?php _e(" Your password will be e-mailed to you after you use the app for the first time (beta or published).", "feed"); ?></p>
<hr>


<form action="admin-post.php" id="live_login" method="post" class="clearfix">
	
	<div class=is_not_logged_in>
		<h4><?php _e("Administration password", "feed"); ?></h4>
		<?php
		echo ($isLoggedin) ? 
			'<input type="password" name="appowner_password" value="'.$mySettings['live_password'].'" placeholder="Password">'.
			'<input type="hidden" name="live_login" value="Login">'.
			'<script>jQuery("#live_login").ajaxSubmit({success: update_live_input});</script>'
		:
			'<input type="password" name="appowner_password" placeholder="Password">';
		?>
		<input type="submit" name="live_login" class="button-primary" value="<?php _e("Login", "feed"); ?>">
	</div>

	<div class=is_logged_in style="display: none;">
		<h4><?php _e("Disable ads", "feed"); ?><br><small><?php _e("if enable ads some of feed.nu's own ads will be displayed", "feed"); ?></small> </h4>
		<input type=radio name="disableAds" value="true"> <?php _e("Yes", "feed"); ?><br>
		<input type=radio name="disableAds" value="false" checked="checked"> <?php _e("No", "feed"); ?><br>
		
		<h4>Admob publisher ID<br></h4>
		<input type=text name="clientAdmobCode">
		
		<h4>Google Analytic<br><small><?php _e("Enter your analytic ID if you want statistic", "feed"); ?></small></h4>
		<input type=text name="clientAnalytics">
		
		<h4><?php _e("Credit Ratio", "feed"); ?><br><small><?php _e("How much credit you want to give us if enabled ads (33-100)", "feed"); ?></small></h4>
		<input type=text name="credsRatio" placeholder="33">
		
		<input type=hidden name=action value=feed_cmd>
		<input type=hidden name=feed_cmd value=get_live_settings><br> <br> <br>
		<input type=submit value="Save" name=save_general class=button-primary>
		
		<span class="display_live_feedback_response"></span>
		
	</div>
</form>
<br>
<a href="#request_live_password">Request new password</a>
