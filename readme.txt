=== uComment ===
Contributors: SPSoft
Author: SPSoft
Tags: ajax comments, reply to comments, comment validation, email notify on reply
Requires at least: 3.0
Tested up to: 3.3
Stable Tag: 1.0.2

Add extra features to your wordpress comments like ajax posting, email notification on reply and field validation.

== Description ==

This plugin adds extra features to your wordpress comment system. Features include:
 
* Choose to clone comment form instead of moving it when the reply link on a comment is clicked.

* Add new comments without refreshing the entire page using AJAX.

* Validate the comment form with javascript before submitting. 

* Add a option for the comment auhtor to be notified whenever a reply to his comment is posted. 


== Installation ==

1. Upload `you-comment-features` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the plugin options page and choose the features you want to use

== Frequently Asked Questions ==

= Why the clone form options doesn't work? =
The clone form option hacks on wordpress default "moveForm" function. If you are using some plugin, theme or custom function that 
will not use the default's comment_reply_link behaviour then the clone form option will not work. Try disabling any comment related
plugins/custom functions.

= I get javascript errors whenever i try to use the validation option = 
If you changed the default field names on your comment form, then the validation will not work. Please use this options only if you 
didn't change any of the default field names.

= Can i use HTML in the default notification email? =
Yes you can. The notification email will be sent in HTML format.

= I Found a Bug or I want to make a feature request. What do I do? =
Use the plugin's forum link on wordpress.org. You can find the link on the plugin's page where you downloaded it.

== Changelog ==

= 1.0.2 =
Fixed a couple of bugs that generated PHP Notices.

= 1.0.1 =
* Original Version
