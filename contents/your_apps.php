<?php
	$timeGenerated = $this->get_pending_build('Android');
	$hasPendingApkBuild = '';
	
	if ( ( abs( $t_diff = time() - $timeGenerated ) ) < 180 ) {
		$hasPendingApkBuild = 'has_pending_build';
	}
	
	$apk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );
	$msg = '';
	$list = ''; 
	$betaButton = '';
	
	if(count($apk) == 0){
		$msg = "You don't have any apk file";
	}
	if($this->get_next_apk_version() == '1.0.0'){
		$msg = 'Start generating your first Android app below';
	}
	foreach ( $apk as $attachid => $attach ) {
		
		$time = $attach->post_date_gmt;
		$time = mysql2date( 'U', $time, false);
			
		if ( ( abs( $t_diff = time() - $time ) ) < 86400 ) {
			if ( $t_diff < 0 )
			$h_time = sprintf( __( '%s from now', 'feed' ), human_time_diff( $time ) );
			else
			$h_time = sprintf( __( '%s ago', 'feed' ), human_time_diff( $time ) );
		} else {
			$h_time = $attach->post_date_gmt;
		}
		
		if($attach->post_excerpt == 'live'){
			$list .= wp_get_attachment_link( $attachid )." [<span class='feed_live'>Live</span>] ".$h_time."<br>";
		}
		if($attach->post_excerpt == 'beta'){
			$list .= wp_get_attachment_link( $attachid )." [<span class='feed_beta'>Beta</span>] ".$h_time."<br>";
			$msg = '';
			$betaButton = '<input type=submit value="Publish APK" name=publish_apk class=button-primary>';
		}
		
	}
	
	

	

?>
<h1>Your apps</h1>
<form id=android_apk class="<?php echo $hasPendingApkBuild; ?>" action=admin-post.php method=post>
	<fieldset>
		<legend>Android</legend><p class=feed_msg>
		<?php echo $msg; ?>
		</p>
		<p style="display: block"><?php echo $list; ?></p>
		<p class=feed_ApkTimer>Checking for new apk in <time>9</time> sek<br><small>(This usually takes about 40-180sek)</small></p>
		<input type=hidden value=feed_cmd name=action>
		<input type=hidden value=handle_apk name=feed_cmd>
		<input type=submit value="Generate new Android apk" name=generate_apk class=button-primary>
		<?php echo $betaButton ?>
		<input type=submit value="Cancel generation" name=cansel_apk class=button-secondary>
	</fieldset>
</form>

<form action="admin-post.php" method="post">
	<fieldset style="float: right">
		<legend>iOS</legend>
		<p>This will very soon be avalible for creation</p>
		<input type="hidden" value="feed_cmd" name="action">
		<input type="hidden" value="handle_apk" name="feed_cmd">
		<input type="submit" value="Generate new iOS IPA" disabled=disabled class=button-primary>
	</fieldset>
</form>

<br class=clear>