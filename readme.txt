=== Just Likes and Dislikes ===
Contributors: GregRoss
Tags: like, dislike, posts, pages, comments
Requires at least: 5.0
Tested up to: 6.5.3
Stable tag: 2.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Like and dislike feature for WordPress.

== Description ==
**Just Likes and Dislikes is a fork of the excellent [Post Like Dislike](https://wordpress.org/plugins/posts-like-dislike/) and [Comment Like and Dislike](https://wordpress.org/plugins/comments-like-dislike/) by [WP Happy Coders](http://wphappycoders.com/)**

Just Likes and Dislikes enables like and dislike icons for posts, pages and comments. Choose between multiple predefined icon sets or use your own custom like/dislike icons, the choice is yours.

Just Likes and Dislikes increases the interaction with the WordPress by enabling likes and dislikes buttons along with the count.

= See full features list below: =
* Select position of like/dislike display; before/after post/comment.
* Disable like/dislikes on any post type.
* Show likes, dislikes or both.
* Choose which order to show likes/dislikes in.
* Definable hover text.
* Choose to display like/dislike counts of zero.
* Choose method to restrict users to a single like/dislike; cookies, IP, logged in users
* 7 available pre-defined icon templates to choose from:
    - Thumbs
    - Hearts
    - Checked/Cross-out
    - Happy/Sad
    - Plus/Minus
    - Up/Down
    - Fire/Extinguisher
* Custom like/dislike icon support
* Icon color selector
* Count color selector
* NEW: Sortable like/dislike columns in post/page admin screens (can be disabled via option)
* NEW: Total like/dislike counts on tags and category admin screens (unsortable due to technical limitations)
* NEW: Front end shortcode to generate Top 10 style tables for liked/disliked content (comments not supported at this time).

= Shortcode =
[just_like_and_dislike id=post_id] or [jlad id=post_id]
Please replace post_id with the id of the post or remove id parameter for considering the post id as the id of global $post object

[just_like_and_dislike_top_table count=10] or [jlad_top_table count=10]
Options available are:
* count - Number of items to display (default 10).
* show_likes - Display a table with the top liked posts in it (default true).
* show_dislikes - Display a table with the top disliked posts in it (default true).
* types - Post types to display, a comma separated list i.e. "post" or "post, page" (default "post").
* show_table_title - Display a title for each table in the format of "Likes for Posts", "Dislikes for Pages", etc. (default true).
* show_row_numbers - Display row numbers for the table (default true).

eg: [jlad_top_table count=3 types="post, pages" show_dislikes=false show_table_title=false]

| | Post Title | 👍️ |
|-|------------|----|
|1| Cool post  |  6 |
|2| Nice post  |  3 |
|3| [no title] |  2 |
| |      Total | 11 |

| | Page Title | 👎️ |
|-|------------|----|
|1| Cool page  |  8 |
|2| Nice page  |  4 |
|3| [no title] |  1 |
| |      Total | 13 |

The table has a css class of jlad_shortcode_table, so you can style it with css, for example:

```
.jlad_shortcode_table thead,
.jlad_shortcode_table tfoot {
	background-color: #000077;
	color: #FFFFFF;
}

.jlad_shortcode_table tr:nth-child(even) {
  background-color: #f2f2f2;
}

.jlad_shortcode_table td:last-child {
	text-align: center;
	width: 20%;
}

.jlad_shortcode_table tfoot td:first-child {
	text-align: right;
}
```

Creates a table with blue background and white text header/footer rows, zebra stripes on the post list, centers the likes/dislikes column and aligns the "Total" in the footer to the right of the column.

= Custom Function =
`<?php echo do_shortcode('[just_like_and_dislike id=post_id]');?>`
Please replace post_id with the id of the post or remove id parameter for considering the post id as the id of global $post object

== Installation ==

1. Install the plugin through the WordPress Plugins screen.
1. Activate the plugin through the Plugins screen in WordPress.
1. Use the Just Likes and Dislikes settings page inside the Posts Menu to configure the plugin.

== Frequently Asked Questions ==
= What does this plugin do ? =
This plugin provides the ability to add the like and dislike buttons for WordPress native posts, pages and comments.

= I have enabled the plugin but like and dislike icons are not being displayed. What may be the reason ? =
The plugin uses the_content filter to append like and dislike icons . So if your active theme's posts template doesn't use the_content filter to display posts content then the plugin won't be able to display like and dislike icons.

You can still use the plugin, but you'll have to add some custom code to your theme (see custom function above) to support it.

= Is there any hooks available to extend the plugin ? =
There are a few available to add new default icon template options, see the code for details.

= I want to display in the post detail template. Do you have a custom function? =
We do have a shortcode [just_likes_and_dislikes] which can also be used as custom function through `<?php echo do_shortcode('[just_likes_and_dislikes]');?>`

= How do I migrate from Posts Like Dislike and Comments Like Dislike? =
There is no built in migration tool at this time, however if you have access to your SQL server (probably phpMyAdmin), you can run some simply queries to migrate the data.  To do so, do the following steps:

*** WARNING: The following steps are destructive and a one time process. ***

1. Disable Posts Like Dislikes, Comments Like Dislike, and Just Likes and Dislikes.
2. Remove the Posts Like Dislikes and Comments Like Dislike plugins from WordPress.
3. Login to your SQL server and run the following SQL queries:
	```
	DELETE FROM `wp_commentmeta` WHERE `meta_key` LIKE 'jlad_%';
	UPDATE `wp_commentmeta` SET `meta_key` = REPLACE(`meta_key`, 'cld_', 'jlad_');

	DELETE FROM `wp_postmeta` WHERE `meta_key` LIKE 'jlad_%';
	UPDATE `wp_postmeta` SET `meta_key` = REPLACE(`meta_key`, 'pld_', 'jlad_');
	```
4. Enable Just Likes and Dislikes.

This above will do two things:
- remove any existing Just Likes and Dislikes data from the database so there are no conflicts with the old data.
- rename the Posts Like Dislike and Comments Like Dislike data to Just Likes and Dislikes data, making it available to only Just Likes and Dislikes.

If you want to go back to Post Likes Dislikes and Comments Likes Dislikes, you can reverse the process:

1. Disable Posts Like Dislikes, Comments Like Dislike, and Just Likes and Dislikes.
2. Remove the Just Likes and Dislikes plugin from WordPress.
3. Login to your SQL server and run the following SQL queries:
	```
	DELETE FROM `wp_commentmeta` WHERE `meta_key` LIKE 'cld_%';
	UPDATE `wp_commentmeta` SET `meta_key` = REPLACE(`meta_key`, 'jlad_', 'cld_');

	DELETE FROM `wp_postmeta` WHERE `meta_key` LIKE 'pld_%';
	UPDATE `wp_postmeta` SET `meta_key` = REPLACE(`meta_key`, 'jlad_', 'pld_');
	```
4. Enable Posts Like Dislikes and Comments Like Dislike.

If there are enough requests I can add this feature to the plugin.

== Screenshots ==

1. Icon template examples
2. Like Dislike Basic Settings
3. Like Dislike Design Settings

== Changelog ==
= 2.8 =
* Release date: June 9, 2024
* Add: Login hint in mouse hover when registration is required to like/dislike.
* Fixed: Misalignment of switches and text in settings.

= 2.7 =
* Release date: June 2, 2024
* Add: Option to disable fontawesome on the front end (pulled from upstream).

= 2.6 =
* Release date: March 1, 2024
* Add: Option to allow row numbers on the top liked/disliked tables.
* Fixed: Default remote IP address set to 127.0.0.1 if no valid address can be found.

= 2.5.1 =
* Release date: Nov 28, 2023
* No changes.

= 2.5 =
* Release date: Nov 28, 2023
* Add: Like/dislike column to categories list (unsortable due to technical limits).
* Add: Like/dislike column to tags list (unsortable due to technical limits).
* Add: Shorter shortcode (jlad = just_like_and_dislike).
* Add: Top liked/disliked tables for the front end.

= 2.4 =
* Release date: Oct 30, 2023
* Fixed: Sorting of like/dislike columns in post/comment admin tables.

= 2.3 =
* Release date: Sept 3, 2023
* Fixed: Inability to disable the first post type.
* Fixed: Make sure when saving/restoring settings that the user can 'manage_settings', aka is an admin.

= 2.2 =
* Release date: April 25, 2023
* Fixed: Incorrect class function call.

= 2.1 =
* Release date: Jan 20, 2023
* Updated: Various translation related changes, thanks @alexclassroom.

= 2.0 =
* Release date: Jan 17, 2023
* Add sortable columns to the admin posts list
* Add filter for Yoast Duplicate Posts so the like/dislike count is reset
* Add proper meta data registration
* Merge Comments Like Dislike plugin functionality
* Removed like/dislike counts from excerpts and embeds
* Remove old internationalization code
* Fix widget width for Gutenberg
* Update the settings page to be clearer/cleaner
* Update font awesome to v6.2.1

= 1.0.8 =
* Release date: July 21, 2022
* WP 6.0 compatibility checked

= 1.0.7 =
* Release date: Feb 13, 2021
* Added post id to shortcode

= 1.0.6 =
* Release date: July 20, 2021
* WP 5.7 compatibility checked

= 1.0.5 =
* Release date: June 6, 2021
* Fixed login restriction mode issue
* Fixed ajax load issue
* Fixed some security issues

= 1.0.4 =
* Release date: Dec 11, 2020
* WP 5.6 compatibility checked

= 1.0.3 =
* Release date: Sept 7, 2020
* Added Post Like Dislike Count Info Metabox
* Added an option to display 0 by default
* Added alt tag in the custom image
* Removed default post type select
* Added [posts_like_dislike] shortcode

= 1.0.2 =
* Release date: April 4, 2020
* WP 5.4 compatibility checked

= 1.0.1 =
* Release date: May 8, 2019
* Added custom post type support
* Updated the backend settings save mechanism
* Added array sanitization functions

= 1.0.0 =
* Release date: May 8, 2019
* Initial plugin commit to wordpress.org repository

== Upgrade Notice ==
There is a new update. Please update to the latest version to get the new features and bug fixes.