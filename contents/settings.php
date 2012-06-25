<?php
$domain = feed_get_blog_domain();

$allSettings = $this->get_feed_settings();

$androidGeneral = $allSettings['Android']['General'];
$globalSettings = $allSettings['Global'];
$globalSettings['packagename']['Standard'] = apply_filters('feed_package_name', $domain);
$globalSettings['feedhost']['Standard'] = get_bloginfo('rss2_url');

echo 
	'<form action=admin-post.php method=post class=clearfix>',
		'<h1 style="background: #ddd; padding: 5px;">Global Settings</h1>',
		$this->add_field($globalSettings, 'feedhost', 'Feed url', 'textarea'),
		$this->add_field($globalSettings, 'app_date_format', 'Date format'),
		$this->add_field($globalSettings, 'custom_date', ''),
		'<h1 style="background: #ddd; padding: 5px;">Android</h1>',
		$this->add_field($androidGeneral, 'apphost', 'Where is the app hosted?'),
		$this->add_field($androidGeneral, 'text_direction', 'Text direction'),
		$this->add_field($androidGeneral, 'ArticleCSS', 'Article CSS', 'textarea'),
		$this->add_field($androidGeneral, 'ArticleExtraHeader', 'Extra header code', 'textarea'),
		$this->add_field($androidGeneral, 'view_after_loading', 'show the loading page or the welcome screen'),
		$this->add_field($androidGeneral, 'packagename', 'Advance - packagename')
		?>
		<div class="feed_feedhost">
			<h4>Advance - Next Version name<br>
				<small>The human readable version name. Must contain 2 dots, for example: 1.3.5</small>
			</h4>
			<input type="text" value="<?php echo $this->get_next_apk_version();?>" name="A_next_version_name" />
		</div>
		<div class="feed_feedhost">
			<h4>Advance - Next Version Code<br>
				<small>Should be the number of times you have publish one app +1</small>
			</h4>
			<input type="text" value="<?php echo $this->get_next_apk_version_code();?>" name="A_next_version_code" />
		</div>
		<br><br>
		<input type=hidden name=action value=feed_cmd>
		<input type=hidden name=feed_cmd value=save_general>
		<input type=submit value=Save name=save_general class=button-primary>
	</form>