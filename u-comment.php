<?php
/*
  Plugin Name: uComment Plugin
  Description: uComment Plugin. Version 1.0.2
  Version: 1.0.2
  Author: SPSoft
  Author URI: http://www.spsoft.in.ua
 */

global $uComment;
$file = __FILE__;
$options = array(
    //All options that will be shown on the Options page should be declared in this array with their default values.
    "on_reply_clone_form" => "1", 
    "on_reply_email_user" => "1", 
    "ajax_post" => "1", 
    "validate_fields" => "1", 
    "email_from" => get_bloginfo('admin_email'), 
    "email_subject" => "You have a new reply on !blog_name", 
    "email_content" => "<p>The user !comment_author left a reply to your comment on !blog_name.</p>\n<p><a href=\"!comment_permalink\">Click here to see the reply</a>.</p>\n<p><strong>Thank you for your comment on <a href=\"!blog_url\">!blog_name</a></strong></p>\n<p><strong>This email was sent automatically. Please don't reply to this email.</strong></p>", //This will be the default email message sent to the user on reply
    "validate_author" => "0",
    "validate_author_message" => "The Name field must contain at least 3 characters",
    "validate_author_required_message" => "The Name is required",
    "validate_email" => "0",
    "validate_email_message" => "The Email field must contain a valid email address",
    "validate_email_required_message" => "The Email is required",
    "validate_website" => "0",
    "validate_website_required" => "0",
    "validate_website_message" => "The Website field must contain a valid url",
    "validate_website_required_message" => "The Website is required",
    "validate_comment_required_message" => "The Comment is required",
    "error_placement" => "0",
    "css_styles" => "",
);

if (!class_exists("uComment")) {

    class uComment {

        var $base_url;
        var $default_options;
        var $options;
        var $message;
        var $status;
        protected $_slug = 'u-comment';

        public function uComment($options) {
            // Load the translation file
            load_plugin_textdomain($this->_slug, false, basename(dirname(__FILE__)) . '/languages');

            // Add class methods to wordpress actions
            add_action('init', array(&$this, 'init'));
            add_action('comment_post', array(&$this, 'update_notify_field'), 9998);
            add_action('comment_post', array(&$this, 'send_email'), 9999);

            // Add class methods to wordpress actions on backend    
            if (is_admin()) {
                register_activation_hook(__FILE__, array(&$this, 'plugin_install'));
                register_deactivation_hook(__FILE__, array(&$this, 'plugin_uninstall'));

                add_action('admin_menu', array(&$this, 'admin_menu'));
                add_action('admin_notices', array(&$this, 'admin_notices'));
                add_action('admin_init', array(&$this, 'admin_init'));
            }

            // Create necessary variables and update options array with values from wordpress database
            $path = str_replace('\\', '/', dirname(__FILE__));
            $path = substr($path, strpos($path, 'plugins') + 8, strlen($path));

            $this->base_url['plugin'] = get_bloginfo('url') . '/wp-content/plugins/' . $path;
            $this->base_url['posts'] = get_bloginfo('url') . '/wp-admin/edit.php?page=';
            $this->base_url['options'] = get_bloginfo('url') . '/wp-admin/options-general.php?page=';
            
            //Get styles
            $options['css_styles'] = file_get_contents($this->base_url['plugin'].'/includes/styles.css');
            $this->default_options = $options;
            $options_from_table = get_option($this->_slug);
            foreach ((array) $options as $default_options_name => $default_options_value) {
                if (!is_null($options_from_table[$default_options_name])) {
                    if (is_int($default_options_value)) {
                        $options[$default_options_name] = (int) $options_from_table[$default_options_name];
                    } else {
                        $options[$default_options_name] = $options_from_table[$default_options_name];
                    }
                }
            }
            $this->options = $options;
            unset($options);
            unset($options_from_table);
            unset($default_options_value);

            // Add class methods that will echo the javascript in the Footer
            if ($this->options['on_reply_clone_form'] == '1') {
                add_action('wp_footer', array(&$this, 'js_on_reply_clone_form'), 9997);
            }
            if ($this->options['validate_fields'] == '1') {
                add_action('wp_footer', array(&$this, 'js_validate_fields'), 9998);
            }
            if ($this->options['ajax_post'] == '1') {
                add_action('wp_footer', array(&$this, 'js_ajax_post'), 9999);
            }
            // Add class method that will place the notify checkbox on the form
            if ($this->options['on_reply_email_user'] == '1') {
                add_action('comment_form', array(&$this, 'html_notify_checkbox'), 9999);
            }
        }

        public function plugin_install() {
            global $wpdb;
            $options_from_table = get_option($this->_slug);
            if (!$options_from_table) {
                update_option($this->_slug, $this->default_options);
                $this->options = $this->default_options;
            }
            $wpdb->query("ALTER TABLE {$wpdb->comments} ADD COLUMN comment_mail_notify TINYINT NOT NULL DEFAULT 0;");
        }

        public function plugin_uninstall() {
            global $wpdb;
            delete_option($this->_slug, $this->default_options);
            $wpdb->query("ALTER TABLE {$wpdb->comments} DROP COLUMN comment_mail_notify;");
        }

        function set_option($optname, $optval) {
            $this->options[$optname] = $optval;
        }

        function save_options() {
            update_option($this->_slug, $this->options);
        }

        // This will run on frontend and backend
        function init() {
            wp_enqueue_script('jquery');
            if (!is_admin()) {
                wp_register_script($this->_slug . 'functions', $this->base_url['plugin'] . '/includes/functions.js');
                wp_enqueue_script($this->_slug . 'functions');
            } 
            add_action('wp_ajax_ucomment_message', array(&$this, 'message'));
            add_action('wp_ajax_nopriv_ucomment_message', array(&$this, 'message'));
            add_action('wp_head', array(&$this, 'css_styles'), 9999);
        }

        // This will run just on backend
        function admin_init() {
            wp_register_style($this->_slug . 'styles', $this->base_url['plugin'] . '/includes/admin.styles.css');
            wp_enqueue_style($this->_slug . 'styles');
        }

        // Create pages in backend navigation menu
        function admin_menu() {
            add_options_page('uComment: Options', 'uComment', 'manage_options', $this->_slug . 'page-options', array(&$this, 'page_options'));
        }

        // This will show messages on backend
        function admin_notices() {
            if ($this->message != '') {
                $message = $this->message;
                $status = $this->status;
                $this->message = $this->status = '';
            }
            if (isset($message)) {
                echo '<div id="message" class="' . (($status != '') ? $status : 'updated') . '">' . "\n";
                echo '<p><strong>' . $message . '</strong></p>' . "\n";
                echo '</div>' . "\n";
            }
        }

        // Function that will show the Options page.
        function page_options() {
            $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
            $tabs = array('general' => __('General', $this->_slug), 'layout' => __('CSS', $this->_slug));
            $links = array();
            foreach ($tabs as $tab => $name) {
                if ($tab == $currentTab) {
                    $links[] = '<a class="nav-tab nav-tab-active" href="?page=' . $this->_slug . 'page-options' . '&tab=' . $tab . '">' . $name . '</a>';
                } else {
                    $links[] = '<a class="nav-tab" href="?page=' . $this->_slug . 'page-options' . '&tab=' . $tab . '">' . $name . '</a>';
                }
            }

            if (isset($_POST['update_options'])) {
                foreach ((array) $this->options as $key => $value) {
                    if (isset($_POST[$key])) {
                        $newval = stripslashes($_POST[$key]);
                        if ($newval != $value) {
                            $this->set_option($key, $newval);
                        }
                    }
                }
                $this->save_options();
                $this->message = __('Options updated.', $this->_slug);
                $this->status = 'updated';
                $this->admin_notices();
            }

            if (isset($_POST['erase_options'])) {
                delete_option($this->_slug, $this->default_options);
                $this->options = $this->default_options;
                $this->message = __('All options were erased.', $this->_slug);
                $this->status = 'updated';
                $this->admin_notices();
            }
            ?>
            <div class="wrap">
                <h2>uComment Features</h2>
                <form action="<?php echo '?page=' . $this->_slug . 'page-options' . '&tab=' . $currentTab; ?>" method="post">
                    <h2 class="tab-nav"><?php
            foreach ($links as $link)
                echo $link;
            ?></h2>
                    <div class="tab-container">
                        <?php
                        switch ($currentTab) :
                            case 'general' :
                                $this->tab_options_general();
                                break;
                            case 'layout' :
                                $this->tab_options_layout();
                                break;
                        endswitch;
                        ?>
                        <p class="submit">
                            <input type="submit" name="update_options" value="<?php esc_attr_e('Update Options &raquo;', $this->_slug); ?>" />
                        </p>
                    </div>
                </form>
            </div>
            <?php
        }

        // Function that will show the options general tab
        function tab_options_general() {
            ?>
            <h3><?php _e('General options', $this->_slug) ?></h3>
            <table class="optiontable form-table">
                <tr valign="top">
                    <th scope="row"><label for="on_reply_clone_form"><?php _e('Clone comment form', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="on_reply_clone_form" name="on_reply_clone_form" value="0" />
                        <input id="on_reply_clone_form" name="on_reply_clone_form" value="1" <?php if ($this->options['on_reply_clone_form'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                        <span class="description"><?php _e('If checked, the comment form will be cloned instead of moved.', $this->_slug) ?></span>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ajax_post"><?php _e('Post comments via ajax', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="ajax_post" name="ajax_post" value="0" />
                        <input id="ajax_post" name="ajax_post" value="1" <?php if ($this->options['ajax_post'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                        <span class="description"><?php _e('If checked the comments will be posted using ajax.', $this->_slug) ?></span>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="on_reply_email_user"><?php _e('Notify users via email', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="on_reply_email_user" name="on_reply_email_user" value="0" />
                        <input id="on_reply_email_user" name="on_reply_email_user" value="1" <?php if ($this->options['on_reply_email_user'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                        <span class="description"><?php _e('If checked, users will have the option to be notified via email whenever a reply to their comment is posted.', $this->_slug) ?></span>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="validate_fields"><?php _e('Validate comment form with javascript', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="validate_fields" name="validate_fields" value="0" />
                        <input id="validate_fields" name="validate_fields" value="1" <?php if ($this->options['validate_fields'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                        <span class="description"><?php _e('If checked, the comment for will be validated before submition.', $this->_slug) ?></span>
                </tr>  
            </table>
            <hr />
            <h3><?php _e('Notification email options') ?></h3>
            <table class="optiontable form-table">
                <tr valign="top">
                    <th scope="row"><label for="email_from"><?php _e('Sender email address', $this->_slug) ?>:</label></th>
                    <td>
                        <input id="email_from" name="email_from" value="<?php echo $this->options['email_from']; ?>" type="text" class="regular-text">
                        <span class="description"><?php _e('The email address to use on the notification email.', $this->_slug) ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="email_subject"><?php _e('Subject', $this->_slug) ?>:</label></th>
                    <td>
                        <input id="email_subject" name="email_subject" value="<?php echo $this->options['email_subject']; ?>" type="text" class="regular-text">
                        <span class="description"><?php _e('The subject used on the notification email.', $this->_slug) ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="email_content"><?php _e('Message', $this->_slug) ?>:</label></th>
                    <td>
                        <textarea class="large-text code" id="email_content" cols="50" rows="10" name="email_content"><?php echo $this->options['email_content']; ?></textarea>
                        <br>
                        <span class="description"><?php _e('The following tags can be used on the subject and message:', $this->_slug); ?> !blog_name, !blog_description, !blog_url, !comment_author, !comment_author_email, !comment_author_url, !comment_content, !comment_permalink</span>
                    </td>
                </tr>
            </table>
            <hr/>
            <h3><?php _e('Validation options') ?></h3>
            <table class="optiontable form-table">
                <tr valign="top">
                    <th scope="row"><label><?php _e('Name', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="validate_author" name="validate_author" value="0" />
                        <label>
                            <input id="validate_author" name="validate_author" value="1" <?php if ($this->options['validate_author'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                            <?php _e('Author field must contain more then 3 characters.', $this->_slug); ?>
                        </label>
                        <br />
                        <label><?php _e('This field is always required.', $this->_slug); ?></label>  
                    </td>
                    <td>
                        <label for="validate_author_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_author_message" name="validate_author_message" value="<?php echo $this->options['validate_author_message']; ?>" type="text" class="regular-text"> 
                        <br />
                        <label for="validate_author_required_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_author_required_message" name="validate_author_required_message" value="<?php echo $this->options['validate_author_required_message']; ?>" type="text" class="regular-text">     
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label><?php _e('Email', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="validate_email" name="validate_email" value="0" />
                        <label>
                            <input id="validate_email" name="validate_email" value="1" <?php if ($this->options['validate_email'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                            <?php _e('Email field must contain a valid email address.', $this->_slug); ?>
                        </label>
                        <br />
                        <label><?php _e('This field is always required.', $this->_slug); ?></label>
                    </td>
                    <td>
                        <label for="validate_email_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_email_message" name="validate_email_message" value="<?php echo $this->options['validate_email_message']; ?>" type="text" class="regular-text"> 
                        <br />
                        <label for="validate_email_required_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_email_required_message" name="validate_email_required_message" value="<?php echo $this->options['validate_email_required_message']; ?>" type="text" class="regular-text">     
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label><?php _e('Website', $this->_slug) ?>:</label></th>
                    <td>
                        <input type="hidden" id="validate_website" name="validate_website" value="0" />
                        <label>
                            <input id="validate_website" name="validate_website" value="1" <?php if ($this->options['validate_website'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                            <?php _e('Website field must contain a valid url.', $this->_slug); ?>
                        </label>
                        <br />
                        <input type="hidden" id="validate_website_required" name="validate_website_required" value="0" />
                        <label>
                            <input id="validate_website_required" name="validate_website_required" value="1" <?php if ($this->options['validate_website_required'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                            <?php _e('This field is required.', $this->_slug); ?>
                        </label>
                    </td>
                    <td>
                        <label for="validate_website_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_website_message" name="validate_website_message" value="<?php echo $this->options['validate_website_message']; ?>" type="text" class="regular-text"> 
                        <br />
                        <label for="validate_website_required_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_website_required_message" name="validate_website_required_message" value="<?php echo $this->options['validate_website_required_message']; ?>" type="text" class="regular-text">     
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label><?php _e('Comment', $this->_slug) ?>:</label></th>
                    <td>
                        <label><?php _e('This field is always required.', $this->_slug); ?></label>
                    </td>
                    <td>
                        <label for="validate_comment_required_message"><? _e('Error message', $this->_slug); ?></label>
                        <input id="validate_comment_required_message" name="validate_comment_required_message" value="<?php echo $this->options['validate_comment_required_message']; ?>" type="text" class="regular-text">     
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label><?php _e('Error placement', $this->_slug) ?>:</label></th>
                    <td colspan="2">
                        <input type="hidden" id="error_placement" name="error_placement" value="0" />
                        <label>
                            <input id="error_placement" name="error_placement" value="1" <?php if ($this->options['error_placement'] == 1) echo 'checked="checked"'; ?> type="checkbox">
                            <?php _e('If checked, the error messages will be shown on top of the comment form. If not, they will show below each field.', $this->_slug); ?>
                        </label>
                    </td>
                </tr>
            </table>    
            <hr />
            <h3><?php _e('Reset to defaults', $this->_slug) ?></h3>
            <table class="optiontable form-table">
                <tr valign="top">
                    <td width="120">
                        <input type="submit" name="erase_options" value="<?php esc_attr_e('Erase Options', $this->_slug); ?>" />
                        <span class="description"><?php _e('Delete all saved options. This will reset all options to their default values.', $this->_slug) ?></span>
                    </td>
                </tr>
            </table>
            <?php
        }

        // Function that will show the options general tab
        function tab_options_layout() {
            ?>
            <h3><?php _e('Cascading Style Sheet') ?></h3>
            <textarea class="large-text code" id="css_styles" cols="50" rows="10" name="css_styles"><?php echo $this->options['css_styles']; ?></textarea>
            <?php
        }

        // Message helper function. This function let's us show erros messages but with translation.
        function message($index = '', $return = FALSE) {
            $messages = array();
            $messages[0] = 'Default Message';
            if (isset($_POST['message'])) {
                if (isset($messages[$_POST['message']])) {
                    _e($messages[$_POST['message']], $this->_slug);
                } else {
                    _e($_POST['message'], $this->_slug);
                }
            } else if (isset($messages[$index])) {
                if ($return == TRUE) {
                    return __($messages[$index], $this->_slug);
                } else {
                    _e($messages[$index], $this->_slug);
                }
            } else {
                if ($return == TRUE) {
                    return __($index, $this->_slug);
                } else {
                    _e($index, $this->_slug);
                }
            }
            exit;
        }

        // Update update notify field in the database
        function update_notify_field($id) {
            global $wpdb;
            if (isset($_POST['comment_mail_notify'])) {
                $wpdb->query("UPDATE {$wpdb->comments} SET comment_mail_notify='1' WHERE comment_ID='$id'");
            }
            return $id;
        }

        // Send the email to the user of the parent comment
        function send_email($id) {
            // Check if we have a parent comment. If we dont we do not need to send email to no one.

            if ($this->options['on_reply_email_user'] == '1') {
                
                $comment = get_comment($id);
                if (empty($comment)) {
                    return $id;
                }
                
                $comment_parent = get_comment($comment->comment_parent);
                if (empty($comment_parent)) {
                    return $id;
                }

                if(intval($comment_parent->comment_mail_notify) === 0) {
                    unset ($comment_parent, $comment);
                    return $id;
                }
                
                $parent_email = trim($comment_parent->comment_author_email);
                if(empty($parent_email)){
                    unset ($comment_parent, $comment, $parent_email);
                    return $id;
		}
                
                if($parent_email === trim($comment->comment_author_email)){ 
                    unset ($comment_parent, $comment, $parent_email);
                    return $id;
		}
                
                // Build Email Message
                $subject = $this->options['email_subject'];
		$subject = str_replace('!blog_name', get_option('blogname'), $subject);
                $subject = str_replace('!blog_description', get_option('blogdescription'), $subject);
                $subject = str_replace('!blog_url', get_option('siteurl'), $subject);
                $subject = str_replace('!comment_author_email', $comment->comment_author_email, $subject);
                $subject = str_replace('!comment_author_url', $comment->comment_author_url, $subject);
                $subject = str_replace('!comment_author', $comment->comment_author, $subject);
                $subject = str_replace('!comment_content', $comment->comment_content, $subject);
                $subject = str_replace('!comment_permalink', get_permalink($comment->comment_post_ID). "#comment-{$comment->comment_parent}", $subject);
                
                $content = $this->options['email_content'];
		$content = str_replace('!blog_name', get_option('blogname'), $content);
                $content = str_replace('!blog_description', get_option('blogdescription'), $content);
                $content = str_replace('!blog_url', get_option('siteurl'), $content);
                $content = str_replace('!comment_author_email', $comment->comment_author_email, $content);
                $content = str_replace('!comment_author_url', $comment->comment_author_url, $content);
                $content = str_replace('!comment_author', $comment->comment_author, $content);
                $content = str_replace('!comment_content', $comment->comment_content, $content);
                $content = str_replace('!comment_permalink', get_permalink($comment->comment_post_ID). "#comment-{$comment->comment_parent}", $content);
                
		$from = "From: ". $this->options['email_from'];
		$headers = "$from\nContent-Type: text/html; charset=" . get_option('blog_charset') . "\n";

		unset($from, $comment_parent, $comment);

		$content = apply_filters('comment_notification_text', $content, $id);
		$subject = apply_filters('comment_notification_subject', $subject, $id);
		$headers = apply_filters('comment_notification_headers', $headers, $id);
                
		@wp_mail($parent_email, $subject, $content, $headers);
                
		unset($parent_email,$subject,$content, $headers);      
            }
            return $id;
        }

        // Function to add the Javascript code to clone the form on reply
        function js_on_reply_clone_form() {
            echo "<script type=\"text/javascript\">overrideMoveForm();</script>\n";
        }

        // Function to add validation fields with Javascript
        function js_validate_fields() {
            //This is just to check if the function is running. Check it on firebug.
            echo '<script type="text/javascript">'."\n";
            echo 'validForm = true;'."\n";
            
            echo 'var formRules = { ';
            echo 'author : { '. (($this->options['validate_author'] == '1') ? 'message : "'.$this->options['validate_author_message'].'",' : '' ).' required : "'. $this->options['validate_author_required_message'] .'"}, ';
            echo 'email : { '. (($this->options['validate_email'] == '1') ? 'message : "'.$this->options['validate_email_message'].'",' : '').' required : "'. $this->options['validate_email_required_message'] .'"}, ';
            echo 'website : { '. (($this->options['validate_website'] == '1') ? 'message : "'.$this->options['validate_website_message'].'"'.(($this->options['validate_website_required'] == '1') ? ',' : '' ) :'') .  (($this->options['validate_website_required'] == '1') ? 'required : "'. $this->options['validate_website_required_message'] .'"' : '' ).'}, ';
            echo 'comment : { required : "'.$this->options['validate_comment_required_message'].'"}';
            echo '};'."\n";
            
            if ($this->options['error_placement'] == '1') {
                echo 'var errorPlacement = "top";'."\n";
            } else {
                echo 'var errorPlacement = "field";'."\n";
            }
            echo 'validateCommentForm(formRules, errorPlacement);'."\n";
            echo '</script>'."\n";
        }
        
        // Function to add the javascript necessary to post the form via ajax
        function js_ajax_post() {
            echo '<script type="text/javascript">ajaxCommentForm();</script>'."\n";
        }
        
        //Function that will place the checkbox on the form to notify user by email
        function html_notify_checkbox() {
            echo '<div class="notify-on-reply"><label><input type="checkbox" name="comment_mail_notify" value="comment_mail_notify" />' . __('Notify me by email if i get a reply') . '</label></div>';
        }
        
        function css_styles() {
            echo '<style type="text/css" media="screen">'."\n";
            echo $this->options['css_styles'];
            echo '</style>'."\n";
        }
    }
}
$uComment = new uComment($options);
