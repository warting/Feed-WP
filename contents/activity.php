<h1>feed.nu news</h1>
<?php
include_once(ABSPATH . WPINC . '/feed.php');
$rss = fetch_feed('http://feeds.feedburner.com/FeednuPluginNews');
$rss_items = $rss->get_items( 0, $rss->get_item_quantity(5) );
if ( !$rss_items ) {
	echo 'no items';
} else {
	foreach ( $rss_items as $item ) {
		echo '<h2><a href="' . $item->get_permalink() . '">' . $item->get_title() . '</a></h2>';
		echo '<p>' . $item->get_content() . '</p>';
	}
}
?>