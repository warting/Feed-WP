<?php

//avoid direct calls to this file where wp core files not present
if(!defined('ABSPATH'))
	die('Please do not load this file directly.');
	
// Load our widget
function load_feed_nu_qr() { register_widget( 'feed_nu_qr' ); }
add_action( 'widgets_init', 'load_feed_nu_qr' );

class feed_nu_qr extends WP_Widget {
	
	function feed_nu_qr()
	{
		
		$this->WP_Widget(
			'feed_nu_qr',
			__('feed.nu - QR','feed_nu_qr'),
			array(
				'classname' => 'feed_nu_qr',
				'description' => __('QR code to download your latest published app','pp_ad')
			),
			array(
				'id_base' => 'feed_nu_qr'
			)
		);
	}
	
	function form( $instance )
	{
		global $blog_id;
		$defaults = array();
		$instance = wp_parse_args( (array) $instance, $defaults );

		$qrUrl = "http://" . feed_get_blog_domain() . '/?fau';
	
		?>
			<div class="feed_nu_qr" style="text-align:center">
				<a href="<?php print $qrUrl; ?>">
					<?php print $this->getQRwithGoogle($qrUrl); ?>
				</a>	
			</div>
		<?php
	}
	
	
	function getQRwithGoogle($chl, $widhtHeight='150',$EC_level='L', $margin='0') {
		return '<img src="http://chart.apis.google.com/chart?chs='.$widhtHeight.'x'.$widhtHeight.'&cht=qr&chld='.$EC_level.'|'.$margin.'&chl='.urlencode($chl).'" alt="QR code" widhtHeight="'.$widhtHeight.'" widhtHeight="'.$widhtHeight.'"/>';
	}
	
	function update( $new_instance, $old_instance )
	{

		$instance = $old_instance;
		
		return $instance;
	}

	function widget( $args, $instance )
	{
		global $blog_id;
		extract( $args );
		
		$qrUrl = "http://" . feed_get_blog_domain() . '/?fau';
	
?>
	<div class="feed_nu_qr" style="text-align:center">
		<a href="<?php print $qrUrl; ?>">
			<?php print $this->getQRwithGoogle($qrUrl); ?>
		</a>	
	</div>
<?php
	}
	
	
}