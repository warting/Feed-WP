<?php
/**
 * @deprecated
 */
function create_feednu_customfeed() {
	wp_redirect(get_bloginfo('rss2_url'));
}
add_action('do_feed_customfeed', 'create_feednu_customfeed');
?>