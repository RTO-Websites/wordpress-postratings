=== Plugin Name ===
Contributors: rtowebsites
Donate link: https://www.rto.de
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
Keywords: post, ratings, rto, rto.de
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple plugin to add star-rating to posts via shortcode.

== Description ==

Simple plugin to add star-rating to posts via  shortcode.

== Installation ==

1. Upload `/postratings` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `<?php echo do_shortcode('[postrating]'); ?>` in your templates on the loop, or use [postrating] shortcode in your post-content.

== Frequently Asked Questions ==


= Can use multiple star-fields? =
Yes, you just have to use different keys in the shortcode:
[postrating key=MyFirstField]
[postrating key=MySecondField]


= I need a callback for rating-success =

Just use
`jQuery(document).on('postrating_success') function(data) {
  console.info(data);
}`

= Can i specify a post id? =

Yes, just set it in the shortcode
`[postrating 541]`


== Screenshots ==


== Changelog ==

= 2.0 =
* Multiple star-fields now possible
* Attach to comments possible


= 1.0 =
* Release
