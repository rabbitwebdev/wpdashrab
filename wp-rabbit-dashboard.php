<?php
/**
 * Plugin Name: WP Rabbit Dashboard
 * Plugin URI: https://github.com/rabbitwebdev/wpdashrab
 * Description: Adds a custom dashboard widget and API integration.
 * Version: 3.0.2
 * Author: Rabbit Web Dev
 * Author URI: https://rabbitwebdesign.co.uk
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
add_action('wp_dashboard_setup', 'custom_dashboard_widget');

add_filter('pre_set_site_transient_update_plugins', 'check_for_custom_plugin_update');

function check_for_custom_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // Define your plugin's GitHub repository information
    $plugin_slug = 'wpdashrab-main'; // The folder name in wp-content/plugins
    $github_repo = 'rabbitwebdev/wpdashrab'; // GitHub username/repo
    $github_api_url = 'https://api.github.com/repos/$github_repo/releases/latest';

    // Fetch the latest release from GitHub
    $response = wp_remote_get($github_api_url, ['headers' => ['User-Agent' => 'WordPress Plugin Updater']]);

    if (is_wp_error($response)) {
        return $transient; // Exit if error
    }

    $release = json_decode(wp_remote_retrieve_body($response));

    if (!isset($release->tag_name)) {
        return $transient; // No version found
    }

    $latest_version = $release->tag_name;
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_slug . '.php');

    if (version_compare($plugin_data['Version'], $latest_version, '<')) {
        $transient->response[$plugin_slug . '/' . $plugin_slug . '.php'] = (object) [
            'slug'        => $plugin_slug,
            'new_version' => $latest_version,
            'package'     => $release->assets[0]->browser_download_url ?? '',
            'url'         => $release->html_url,
        ];
    }

    return $transient;
}

add_filter('auto_update_plugin', function ($update, $item) {
    return ($item->slug === 'wp-rabbit-dashboard') ? true : $update;
}, 10, 2);


function custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_widget_id', // Widget ID
        'Your Website Dashboard!', // Widget Title
        'custom_dashboard_widget_display' // Callback function to display content
    );
}

function custom_dashboard_widget_display() {
    echo '<h3>WP Rabbit Dashboard!</h3>';
      echo '<div id="custom-api-data">
        <p>Loading data...</p>
    </div>';
     echo '<hr>';
    echo '<a href="/video-instructions/" target="_blank" class="button button-primary">Video Instructions</a>';
     echo '<a href="/blocks/" target="_blank" class="button button-primary">Block Samples</a>';
      echo '<hr>';
      echo '<a href="https://rabbitwebdesign.co.uk" target="_blank" class="button button-primary">Visit Website</a>';
      echo '<p>If you need more info please contact me <a href="mailto:hey@rabbitwebdesign.co.uk">hey@rabbitwebdesign.co.uk</a></p>';
     echo '<hr>';
}

add_action('wp_dashboard_setup', 'custom_dashboard_widget_to_top');

function custom_dashboard_widget_to_top() {
    global $wp_meta_boxes;

    $widget = $wp_meta_boxes['dashboard']['normal']['core']['custom_widget_id'];
    unset($wp_meta_boxes['dashboard']['normal']['core']['custom_widget_id']);

    $wp_meta_boxes['dashboard']['normal']['high']['custom_widget_id'] = $widget;
}

add_action('admin_head', 'custom_dashboard_widget_styles');

function custom_dashboard_widget_styles() {
    echo '<style>
        #custom_widget_id {
            background:rgb(255, 255, 255);
             border: 4px solid rgb(6, 33, 117);
             border-radius: 6px;
             box-shadow: 1px 1px 2px 1px #00000036;
        }
        #custom_widget_id .postbox-header {
        background:rgb(255, 255, 255);
        }
        #custom_widget_id h2 {
            color:rgb(6, 33, 117);
            font-size:20px;
            font-weight:900;
        }
        #custom_widget_id .button-primary {
  background:rgb(6, 33, 117);
  border-color: rgb(6, 33, 117);
  color: #fff;
 box-shadow: 1px 1px 2px 1px #00000021;
  width: fit-content;
  margin-bottom: 8px;
  display: block;
  letter-spacing: 1px;
  padding: 1px 20px;
    padding-top: 1px;
    padding-right: 20px;
    padding-bottom: 1px;
    padding-left: 20px;
  font-size: 14px;
  font-weight: 500;
}
    </style>';
}

add_action('wp_dashboard_setup', 'add_custom_api_dashboard_widget');

function add_custom_api_dashboard_widget() {
    wp_add_dashboard_widget('custom_api_widget', 'Live Data from API', 'display_custom_api_widget');
}
function display_custom_api_widget() {
    $client_id = get_option('custom_client_id', 'default-client'); // Store the unique client ID
    ?>
   

   <script>
    document.addEventListener("DOMContentLoaded", function() {
        const clientID = "<?php echo esc_js($client_id); ?>"; 

        fetch(`https://rabbitwebdesign.co.uk/wp-json/custom-api/v1/dashboard-data/?client_id=${clientID}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('custom-api-data');

                if (!container) {
                    console.error("Element with ID 'custom-api-data' not found.");
                    return;
                }

                if (data.error) {
                    container.innerHTML = `<p style="color:red;">Error: ${data.error}</p>`;
                    return;
                }

                const expiryDate = new Date(data.expiry);
                const today = new Date();

                if (today > expiryDate) {
                    container.innerHTML = `<p>No active promotions or messages.</p>`;
                    return;
                }

               

                let content = `
                    <p><strong>⏳ Expires on:</strong> ${data.expiry || "N/A"}</p>
                `;

                 // Add image if valid
                  if (data.intro) {
                   content += `<h3><strong>📢 Intro:</strong> ${data.intro || "N/A"}</h3>`;
                }

                  // Add image if valid
                  if (data.promotion) {
                   content += `<p><strong>🔔 Promotion:</strong> ${data.promotion}</p>`;
                }

                  // Add image if valid
                  if (data.message) {
                   content += `<p><strong>📢 Message:</strong> ${data.message || "N/A"}</p>`;
                }

                  // Add image if valid
                  if (data.announcement) {
                   content += `<p><strong>🔔 Announcement:</strong> ${data.announcement}</p>`;
                }

                // Helper function to check if a URL is valid
                const isValidUrl = (string) => {
                    try {
                        new URL(string);
                        return true;
                    } catch (_) {
                        return false;
                    }
                };

                // Add image if valid
                if (data.image && isValidUrl(data.image)) {
                    content += `<p><img src="${data.image}" style="max-width:100%; height:auto;"></p>`;
                }

                // Add video if valid
                if (data.video && isValidUrl(data.video)) {
                    content += `
                        <video src="${data.video}" id="player" class="js-player" controls 
                            data-plyr-config='{ "title": "Example Title" }'></video>
                    `;
                }

                content += `<p><strong>Last Updated:</strong> ${data.date || "N/A"}</p>`;

                container.innerHTML = content;
            })
            .catch(error => {
                document.getElementById('custom-api-data').innerHTML = '<p style="color:red;">Error fetching data!</p>';
                console.error("API Fetch Error:", error);
            });
    });
</script>
    <?php
}

// Add the widget to the dashboard
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('custom_api_widget', 'Live Data from API', 'display_custom_api_widget');
});


add_action('admin_menu', function() {
    add_menu_page('Client API Settings', 'API Settings', 'manage_options', 'client-api-settings', 'client_api_settings_page_html');
});

function client_api_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['client_id'])) {
        update_option('custom_client_id', sanitize_text_field($_POST['client_id']));
        echo '<div class="updated"><p>Client ID updated!</p></div>';
    }

    $client_id = get_option('custom_client_id', 'default-client');
    ?>

    <div class="wrap">
        <h1>Client API Settings</h1>
        <form method="post">
            <label><strong>Enter Your Client ID:</strong></label>
            <input type="text" name="client_id" value="<?php echo esc_attr($client_id); ?>" required>
            <br><br>
            <input type="submit" class="button-primary" value="Save">
        </form>
    </div>
    <?php
}
