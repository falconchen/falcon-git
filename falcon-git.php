<?php
/*
Plugin Name: Falcon Git
Plugin URI: https://www.cellmean.com/falcon-git
Description: auto deploy via git webhook
Version: 0.3
Author: Falcon
Author URI: https://www.cellmean.com
*/
$falcon_git = new FalconGit();

class FalconGit
{
    public function  __construct()
    {
        $this->base_file = plugin_basename(__FILE__);

        add_action('query_vars', array(&$this, 'add_query_vars'));

        add_action("parse_request", array(&$this, 'callback'));

        add_action('admin_menu', array(&$this, 'admin_menu'), 2);

        $this->settings = get_option('falcon_git_settings');
    }

    public function admin_menu()
    {
        add_menu_page(__('Falcon Fit', 'FG'), __('Falcon Git', 'FG'), 'manage_options', __FILE__, array(&$this, 'setting_page'), 'dashicons-download', 3);
    }

    public function add_query_vars($public_query_vars)
    {
        if (!in_array('from', $public_query_vars)) {
            $public_query_vars[] = 'from';
        }
        return $public_query_vars;

    }

    public function callback($request)
    {

        if (isset($request->query_vars['from']) && $request->query_vars['from'] == 'git') {
            $this->_callback($request);
            die();

        };

    }

    protected function _callback($request)
    {
        $upload_dir_arr = wp_upload_dir();
        $update_dir = $upload_dir_arr['basedir'] . '/git';
        if (!is_dir($update_dir)) {
            mkdir($update_dir);
        }
        global $HTTP_RAW_POST_DATA;
        if (empty($HTTP_RAW_POST_DATA)) {
            $data = file_get_contents('php://input');
        } else {
            $data =& $HTTP_RAW_POST_DATA;
        }

        //file_put_contents($update_dir . '/data_raw.txt', var_export($data,true), FILE_APPEND);
        $data = urldecode($data);

        if (preg_match("#\{.*\}#i", $data, $matches)) {

            $data_arr = json_decode($matches[0], true);

            if ($data_arr['password'] == $this->settings['password'] && $data_arr['hook_name'] == "push_hooks") {
                if (!isset($data_arr['push_data']) || $data_arr['push_data']['ref'] !== "refs/heads/" . $this->settings['branch']) {
                    return;
                }
                echo $info = sprintf("[%s] %s %s\n",
                    $data_arr['push_data']['user']['time'],

                    $data_arr['push_data']['user_name'],

                    $data_arr['push_data']['after']
                );

                file_put_contents($update_dir . '/data.txt', $info, FILE_APPEND);
            }
        }

    }

    public function setting_page()
    {

        if ($_POST['settings_submit']) {

            if (check_ajax_referer($this->base_name)) {
                update_option('falcon_git_settings', array('password' => $_POST['password'], 'branch' => $_POST['branch']));
                $this->settings = get_option('falcon_git_settings');
            }
        }

        if ($this->settings === false) {
            $this->settings['password'] = "";
            $this->settings['branch'] = "master";
        }


        ?>
        <style>
            .setting-form label {
                width: 10%;
                display: inline-block;
                font-size: 1.2em;
            }
        </style>
        <div class="wrap">
            <form class="setting-form" action="" method="POST">
                <div class="icon32" id="icon-edit"><br></div>
                <h2><?php _e("Falcon Git Settings", 'FG') ?></h2>
                <hr/>
                <p>
                    <label for="password"><?php _e("Password", "FG") ?>:</label>
                    <input name="password" value="<?php echo $this->settings['password']; ?>"/>
                </p>

                <p>
                    <label for="branch"><?php _e("Branch", "FG"); ?>:</label>
                    <input name="branch" value="<?php echo $this->settings['branch']; ?>">
                </p>

                <div class="submit"><input type="submit" name="settings_submit" id="settings_submit"
                                           class="button-primary" value="<?php _e('Submit', 'FG'); ?>"/></div>
                <?php wp_nonce_field($this->base_name); ?>
            </form>
        </div>
        <?php
    }
}