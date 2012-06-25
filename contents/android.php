<?php
//TODO: Make a grater post page
//TODO: Think we have missed the update dialog
//TODO: make a GUI for the other <input> not displayed in the phone

$settings = $this->get_feed_settings();
$androidLang = $this->get_user_value($settings['Android']['Language']);
$androidColor = $this->get_user_value($settings['Android']['Color']);
$androidImage = $this->get_user_value($settings['Android']['Image']);

foreach ($androidImage as $key => $value) {
	$gui[$key] = $value['UserValue'];
	$guiFile[$key] = '<input type="file" name="'.$key.'" />';
}


foreach ($androidLang as $key => $value) {
	$value['Standard'] = str_replace('\n', "\n", $value['Standard']);
	$value['UserValue'] = str_replace('\n', "\n", $value['UserValue']);
	$guiInput[$key] = '<input type="text" value="'.$value['UserValue'].'" placeholder="'.$value['Standard'].'" name="'.$key.'" />';
	$guiTextarea[$key] = '<textarea placeholder="'.$value['Standard'].'" name="'.$key.'">'.$value['UserValue'].'</textarea>';
}

foreach ($androidColor as $key => $value) {
	$guiColor[$key] = '<input type="text" class=colorpicker_select placeholder="'.$value['Standard'].'" value="'.$value['UserValue'].'" name="'.$key.'" />';
}

?>


<h1>Android</h1>
<form class="clearfix" action="admin-post.php" entype="multipart/form-data" method="post">
	<div class=feed_phone>
		<div class=feed_screen>
			<div class=feed_page_apps>
				<p style="margin-top: 1px;"><img class=small_icon data-name=icon src=<?php echo $gui['icon']; ?>><span data-name=new_items></span></p>
				<p style="text-align: center; width: 77px; color: #fff"><img width=45 height=45 data-name=icon src=<?php echo $gui['icon'] ?>><br><span data-name=app_name></span></p>
			</div>
				
			<div class=feed_page_splash data-background=1>
				<img src=<?php echo WP_PLUGIN_URL; ?>/feed-nu/images/android_bar.png width=100%>
				<p data-name=splash_loading data-text=1></p>
			</div>
			
			<div class=feed_page_home data-background=1>
				<img src=<?php echo WP_PLUGIN_URL; ?>/feed-nu/images/android_bar.png width=100%>
				<img data-name=top_image_start src=<?php echo $gui['top_image_start']; ?> width=100%>
				<ul class=data data-listbackground=1 data-text=1 data-border=1>
					<li>
						<p data-name=feedDescription></p>
					</li>
				</ul>
				<div data-name=mybtn_normal data-src="<?php echo $gui['mybtn_normal']; ?>">
					<a data-text="1" data-name=start_reading></a>
				</div>
				<table class=feed_menu cellpadding=0 cellspacing=0>
				  <tr>
				    <th><span data-name=settings></span></th>
				    <th><span data-name=menu_about></span></th>
				  </tr>
				</table>
			</div>
									
			<div class=feed_page_article data-background=1>
				<img src=<?php echo WP_PLUGIN_URL; ?>/feed-nu/images/android_bar.png width=100%>
				<img data-name=top_image_listview src=<?php echo $gui['top_image_listview']; ?> width=100%>
				<ul class=data data-listbackground=1 data-text=1 data-border=1>
					<li class=feed_read data-border=1>
						<img data-name=is_read_icon src=<?php echo $gui['is_read_icon']; ?>>
						Title
						<time>2011/06/27 23:55</time>
					</li>
					<li class=feed_read data-border=1>
						<img data-name=is_read_icon src=<?php echo $gui['is_read_icon']; ?>>
						Title
						<time>2011/06/25 13:20</time>
					</li>
					<li data-border=1>
						<img data-name=item_icon src=<?php echo $gui['item_icon']; ?>>
						Unread Title
						<time>2011/06/25 08:07</time>
					</li>
					<li class=feed_read data-border=1>
						<img data-name=is_read_icon src=<?php echo $gui['is_read_icon']; ?>>
						Title
						<time>2011/06/10 15:15</time>
					</li>
					<li data-border=1>
						<img data-name=item_icon src=<?php echo $gui['item_icon']; ?>>
						Unread Title
						<time>2011/06/07 23:55</time>
					</li>
					<li>
						<img data-name=item_icon src=<?php echo $gui['item_icon']; ?>>
						Unread Title
						<time>2011/06/06 14:59</time>
					</li>
				</ul>
				
				<table class=feed_menu cellpadding=0 cellspacing=0>
				  <tr>
				    <th><span data-name=settings></span></th>
				    <th><span data-name=sync_now></span></th>
				  </tr>
				  <tr>
				    <th><span data-name=reset_database></span></th>
				    <th><span data-name=mark_all_as_read></span></th>
				  </tr>
				  <tr>
				    <th colspan=2><span data-name=menu_about></span></th>
				  </tr>
				</table>
			</div>

			<div class=feed_page_description data-background=1>
				<img src=<?php echo WP_PLUGIN_URL; ?>/feed-nu/images/android_bar.png width=100%>
				<img data-name=top_image_description src=<?php echo $gui['top_image_description']; ?> width=100%>
				<ul class=data data-listbackground=1 data-text=1 data-border=1>
					<li data-border=1>
						<h1>Title</h1>
						<p>blog post</p>
						<a data-name=read_more></a>
					</li>
				</ul>
				
				<table class=feed_menu cellpadding=0 cellspacing=0>
				  <tr>
				    <th colspan=2><span data-name=share></span></th>
				  </tr>
				</table>				
			</div>

			<div class=feed_page_setting>
				<img src=<?php echo WP_PLUGIN_URL; ?>/feed-nu/images/android_bar.png width=100%>
				<div class=feed_header>
					<h1 data-name=app_name></h1>
					<h1 data-name=settings_title></h1>
				</div>
										
				<ul class="feed_settings">
					<li>
						<h2 data-name=setting_title_autoupdate></h2>
						<a data-name=setting_summary_autoupdate></a>
					</li>
					<li>
						<h2 data-name=setting_title_hideread></h2>
						<a data-name=setting_summary_hideread></a>
					</li>
					<li>
						<h2 data-name=setting_title_debugmode></h2>
						<a data-name=setting_summary_debugmode></a>
					</li>
				</ul>
										
			</div>
			<div>Dont remove</div>
				
		</div>
	</div>
							
	<div class="feed_tools">
		<?php 
			echo
				$this->add_div('apps', __('App icon'), $guiFile['icon']),
				$this->add_div('apps', __('App name:'), $guiInput['app_name']),
				$this->add_div('apps', __('Notification:'), $guiInput['new_items']),

				$this->add_div('splash', __('Loading text'), $guiInput['splash_loading']),
				$this->add_div('splash', __('Progress dialog:'), $guiInput['pregress_dialog_message']),
				
				$this->add_div('home', __('Home image:'), $guiFile['top_image_start']),
				$this->add_div('home', __('text color:'), $guiColor['text']),
				$this->add_div('home', __('Border color:'), $guiColor['border']),
				$this->add_div('home', __('Background:'), $guiColor['background']),
				$this->add_div('home', __('listback ground:'), $guiColor['listbackground']),
				$this->add_div('home', __('Description:'), $guiTextarea['feedDescription']),
				$this->add_div('home', __('Start reading:'), $guiInput['start_reading']),
				$this->add_div('home', __('Settings:'), $guiInput['settings']),
				$this->add_div('home', __('About:'), $guiInput['menu_about']),
				$this->add_div('home', __('About text:'), $guiTextarea['about_text']),
				$this->add_div('home', '<a href="http://draw9patch.com" class="tooltip" target="_blank">?<span>It\'s important to upload a valid nine patch image, click on this link to get help generating one</span></a>'.__('Button normal:'), $guiFile['mybtn_normal']),
				$this->add_div('home', '<a href="http://draw9patch.com" class="tooltip" target="_blank">?<span>It\'s important to upload a valid nine patch image, click on this link to get help generating one</span></a>'.__('Button focus:'), $guiFile['mybtn_focus']),
				$this->add_div('home', '<a href="http://draw9patch.com" class="tooltip" target="_blank">?<span>It\'s important to upload a valid nine patch image, click on this link to get help generating one</span></a>'.__('Button pressed:'), $guiFile['mybtn_pressed']),
				
				$this->add_div('article', __('Article image:'), $guiFile['top_image_listview']),
				$this->add_div('article', __('Unread icon:'), $guiFile['item_icon']),
				$this->add_div('article', __('Read icon:'), $guiFile['is_read_icon']),
				$this->add_div('article', __('No unread post:'), $guiInput['no_unread_items']),
				$this->add_div('article', __('No post at all:'), $guiInput['no_items_at_all']),
				$this->add_div('article', __('Sync now:'), $guiInput['sync_now']),
				$this->add_div('article', __('Delete all posts:'), $guiInput['reset_database']),
				$this->add_div('article', __('Mark all post as read:'), $guiInput['mark_all_as_read']),
				
				$this->add_div('description', __('Post image:'), $guiFile['top_image_description']),
				$this->add_div('description', __('Read more:'), $guiInput['read_more']),
				$this->add_div('description', __('Share:'), $guiInput['share']),
				$this->add_div('description', __('Share title:'), $guiInput['share_title']),
				$this->add_div('description', __('Share message:'), $guiInput['share_message']),
				
				$this->add_div('setting', __('Setting Header:'), $guiInput['settings_title']),
				$this->add_div('setting', __('Autoupdate:'), $guiInput['setting_title_autoupdate']),
				$this->add_div('setting', __('Autoupdate desc:'), $guiInput['setting_summary_autoupdate']),
				$this->add_div('setting', __('Hide read:'), $guiInput['setting_title_hideread']),
				$this->add_div('setting', __('Hide read desc:'), $guiInput['setting_summary_hideread']),
				$this->add_div('setting', __('Debug mode:'), $guiInput['setting_title_debugmode']),
				$this->add_div('setting', __('Debug mode desc:'), $guiInput['setting_summary_debugmode'])
				//$this->add_div('setting', __('Autoupdate desc:'), $guiInput['setting_title_updateinterval'])
				
		?>
		<div class="feed_nav">
			<button class="button feed_prev alignleft" style="border-bottom-right-radius: 0; border-top-right-radius: 0; padding: 0 0 4px;" disabled>Prev</button>
			<button class="button feed_next alignleft" style="border-bottom-left-radius: 0; border-top-left-radius: 0; padding: 0 0 4px;">Next</button>
			<input type=hidden value=feed_cmd name=action>
			<input type=hidden value=save_android_gui name=feed_cmd>
			<input type=hidden value=0 name=valid_by_canvas>
			<input type=submit value=Save class=button-primary style="float:right">
		</div>
								
	</div>
</form>