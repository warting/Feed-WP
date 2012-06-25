<?php
$client = new FeedNuClient();
$domain = feed_get_blog_domain();
$adminemail = get_option("admin_email");
$packagename = apply_filters('feed_full_package_name', $domain);
$e =  $client->request_new_live_password($adminemail, $packagename);
print_r($e);
?>