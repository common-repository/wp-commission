<?php
defined('WPINC') or die;

class WP_Commission_Plugin extends WP_Stack_Plugin2
{
	const INVALID_LICENSE = 'invalid_license';
	const INVALID_SITE = 'invalid_site';
	const LICENSE_EXPIRED = 'license_expired';

	/**
	 * Constructs the object, hooks in to 'plugins_loaded'
	 */
	protected function __construct()
	{
		$this->hook('plugins_loaded', 'add_hooks');
	}

	/**
	 * Adds hooks
	 */
	public function add_hooks()
	{
		$this->hook('init');
		$this->hook('transition_post_status');
		$this->hook('recent_books');
		$this->hook('wp_enqueue_scripts');
		$this->hook('wp_footer');
		$this->hook('admin_menu');
		$this->hook('wp_ajax_amazon_books', 'ajax');
		$this->hook('wp_ajax_nopriv_amazon_books', 'ajax');
		$this->hook('admin_notices');
		add_action('rest_api_init', function () {
			register_rest_route('wp-commission/v1', '/post-metadata', array(
				'methods' => 'GET',
				'callback' => 'wp_commission_get_post_metadata',
			));
			register_rest_route('wp-commission/v1', '/sync-batch', array(
				'methods' => 'POST',
				'callback' => 'wp_commission_sync_batch',
			));
			register_rest_route('wp-commission/v1', '/expand', array(
				'methods' => 'GET',
				'callback' => 'wp_commission_expand',
			));
		});
	}

	public function admin_notices()
	{
		$admin_notice = $this->get_pending_admin_notice();
		if ($admin_notice) {
			echo $admin_notice;
		}

		// Only show WPC notices on the dashboard or on WP pages.
		$self = $_SERVER['PHP_SELF'];
		$page_is_wpc = strpos($_SERVER['QUERY_STRING'], 'page=wpcommission') !== false; // TODO make this bulletproof
		$on_dashboard = wp_commission_endsWith($self, 'wp-admin/index.php');
		$on_admin_page = wp_commission_endsWith($self, 'wp-admin/admin.php') && $page_is_wpc;
		if (!($on_dashboard || $on_admin_page)) {
			return;
		}

		// Only show to admins.
		if (!current_user_can('manage_options')) {
			return;
		}

		if (!$this->correctly_configured()) {
			if (!get_option('wp-commission-license-key')) {
				echo "<div class=\"notice is-dismissible notice-error\"><p>Please enter a WP Commission license key. Licenses are available for purchase <a target=\"_blank\" href=\"https://wpcommission.com/activate\">here</a>.</p></div>";
				return;
			}

			$response = $this->client->make_check_request();
			if (!$response) {
				echo "<div class=\"notice is-dismissible notice-error\"><p>WP Commission is having trouble communicating with the server right now. Please refresh this page to see if the problem persists.</p></div>";
				return;
			} else if ($response->getStatusCode() == 200) {
				return;
			}
			$body = json_decode($response->getBody());
			echo "<div class=\"notice is-dismissible notice-error\"><p>" . $body->{'message'} . "</p></div>";
		}
	}

	/**
	 * @return a string if the key is valid, or false.
	 */
	public function get_amazon_associate_id()
	{
		$response = $this->client->make_check_request();
		if (!$response) {
			return false;
		} elseif ($response->getStatusCode() == 200) {
			return json_decode($response->getBody())->{'key'};
		}
		return false;
	}

	/**
	 * @return a string if the key is valid, or false.
	 */
	public function get_plan_type()
	{
		$response = $this->client->make_check_request();
		if (!$response) {
			return false;
		} elseif ($response->getStatusCode() == 200) {
			return json_decode($response->getBody())->{'plan_type'};
		}
		return false;
	}

	/**
	 * @return bool depending on correct license and site config.
	 */
	public function correctly_configured()
	{
		$response = $this->client->make_check_request();
		if (!$response) {
			return false;
		}
		return $response->getStatusCode() == 200;
	}

	/**
	 * Initializes the plugin, registers textdomain, etc
	 */
	public function init()
	{
		// Are we in local dev mode?
		if (strpos(parse_url(home_url(), PHP_URL_HOST), '.dev') !== false) {
			$this->test = true;
		}

		$this->client = new WPCommissionClient();

		add_shortcode('wpcommission', [$this, 'recent_books']);
		add_shortcode('wpcommission-no-rewrite', [$this, 'no_rewrite']);
		add_shortcode('wpcommission_no_rewrite', [$this, 'no_rewrite']);

		add_action('admin_post_change_license_key', [$this, 'change_license_key']);
		add_action('admin_post_pin_item', [$this, 'pin_item']);
		add_action('admin_post_unpin_item', [$this, 'unpin_item']);
		add_action('admin_post_blacklist_item', [$this, 'blacklist_item']);
		add_action('admin_post_unblacklist_item', [$this, 'unblacklist_item']);
		add_action('admin_post_manually_add_item', [$this, 'manually_add_item']);
	}

	public function admin_menu()
	{
		$hook = add_management_page('WP Commission', 'WP Commission', 'manage_options', 'wp-commission', [$this, 'settings_page']);
		add_menu_page('WP Commission', 'WP Commission', 'manage_options', 'wpcommission', [$this, 'settings_page'], 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNTEycHgiIGhlaWdodD0iNTEycHgiIHZpZXdCb3g9IjAgMCA0NzUuNDUyIDQ3NS40NTEiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ3NS40NTIgNDc1LjQ1MTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxnPjxwYXRoIGQ9Ik00NjguMDgzLDExOC4zODVjLTMuOTktNS4zMy05LjYxLTkuNDE5LTE2Ljg1NC0xMi4yNzVjMC4zODcsNi42NjUtMC4wODYsMTIuMDktMS40MiwxNi4yODFsLTg1LjY1LDI4MS43ODkgICBjLTEuNTI2LDQuOTQ4LTQuODU5LDguODk3LTkuOTkyLDExLjg0OGMtNS4xNDEsMi45NTMtMTAuNDY5LDQuNDI4LTE1Ljk4OSw0LjQyOEg3NC42NmMtMjIuODQsMC0zNi41NDItNi42NTItNDEuMTEyLTE5Ljk4NSAgIGMtMS45MDMtNS4xNC0xLjgwNy05LjIyOSwwLjI4OC0xMi4yNzVjMi4wOTItMi44NTcsNS43MDgtNC4yODgsMTAuODUtNC4yODhoMjQ4LjEwMmMxNy43MDIsMCwyOS45My0zLjI4NSwzNi42ODgtOS44NTIgICBjNi43NjMtNi41NjcsMTMuNTY1LTIxLjE3NywyMC40MTMtNDMuODI0bDc4LjIyOC0yNTguNjY5YzQuMTg2LTE0LjA4NCwyLjQ3NC0yNi40NTctNS4xNDEtMzcuMTEzcy0xOC40NjItMTUuOTg3LTMyLjU0OC0xNS45ODcgICBIMTczLjE2M2MtMi40NzQsMC03LjMyOSwwLjg1NC0xNC41NjIsMi41NjhsMC4yODQtMC44NTljLTUuMzMtMS4xNC05Ljg1MS0xLjY2Mi0xMy41NjItMS41NzEgICBjLTMuNzEsMC4wOTktNy4xMzcsMS4xOTItMTAuMjc3LDMuMjg5Yy0zLjE0LDIuMDk0LTUuNjY0LDQuMzI4LTcuNTY2LDYuNzA2Yy0xLjkwMywyLjM4LTMuNzYxLDUuNDI2LTUuNTY4LDkuMTM2ICAgYy0xLjgwNSwzLjcxNS0zLjMzLDcuMTQyLTQuNTY3LDEwLjI4MmMtMS4yMzcsMy4xNC0yLjY2Niw2LjQ3My00LjI4MSw5Ljk5OGMtMS42MiwzLjUyMS0zLjE4Niw2LjQyMy00LjcxLDguNzA2ICAgYy0xLjE0MywxLjUyMy0yLjc1OCwzLjUyMS00Ljg1NCw1Ljk5NmMtMi4wOTEsMi40NzQtMy44MDUsNC42NjQtNS4xMzcsNi41NjdjLTEuMzMxLDEuOTAzLTIuMTksMy42MTYtMi41NjgsNS4xNCAgIGMtMC4zNzgsMS43MTEtMC4xOSw0LjIzMywwLjU3MSw3LjU2NmMwLjc2LDMuMzI4LDEuMDQ3LDUuNzUzLDAuODU0LDcuMjc3Yy0wLjc2LDcuMjMyLTMuMzc4LDE2LjQxNC03Ljg0OSwyNy41NTIgICBjLTQuNDcxLDExLjEzNi04LjUyLDE5LjE4LTEyLjEzNSwyNC4xMjZjLTAuNzYxLDAuOTUtMi44NTMsMy4wOTItNi4yOCw2LjQyNGMtMy40MjcsMy4zMy01LjUyLDYuMjMtNi4yNzksOC43MDQgICBjLTAuNzYyLDAuOTUxLTAuODEsMy42MTctMC4xNDQsNy45OTRjMC42NjYsNC4zOCwwLjkwNyw3LjQyMywwLjcxNSw5LjEzNmMtMC43NjUsNi40NzMtMy4xNCwxNS4wMzctNy4xMzksMjUuNjk3ICAgYy0zLjk5OSwxMC42NTctNy45OTQsMTkuNDE0LTExLjk5MywyNi4yNjVjLTAuNTY5LDEuMTQxLTIuMTg1LDMuMzI4LTQuODUzLDYuNTY3Yy0yLjY2MiwzLjIzNy00LjI4Myw1LjkwMi00Ljg1Myw3Ljk5ICAgYy0wLjM4LDEuNTIzLTAuMzMsNC4xODgsMC4xNDQsNy45OTRjMC40NzMsMy44MDYsMC40MjYsNi42Ni0wLjE0NCw4LjU2MmMtMS41MjEsNy4yMjgtNC4zNzcsMTUuOTQtOC41NjUsMjYuMTI1ICAgYy00LjE4NywxMC4xNzgtOC40NywxOC44OTYtMTIuODUxLDI2LjEyMWMtMS4xMzgsMS45MDYtMi43MTIsNC4xNDUtNC43MDgsNi43MTFjLTEuOTk5LDIuNTY2LTMuNTY4LDQuODA1LTQuNzExLDYuNzA3ICAgYy0xLjE0MSwxLjkwMy0xLjkwMywzLjkwMS0yLjI4NCw1Ljk5NmMtMC4xOSwxLjE0MywwLjA5OCwyLjk5OCwwLjg1OSw1LjU3MWMwLjc2LDIuNTY2LDEuMDQ3LDQuNjEyLDAuODU0LDYuMTQgICBjLTAuMTkyLDIuNjYyLTAuNTcsNi4xODctMS4xNDEsMTAuNTY3Yy0wLjU3Miw0LjM3My0wLjg1OSw2LjkzOS0wLjg1OSw3LjY5OWMtNC4xODcsMTEuNDI0LTMuOTk5LDIzLjUxMSwwLjU3MiwzNi4yNjkgICBjNS4zMywxNC44MzgsMTQuNzk3LDI3LjM2LDI4LjQwNiwzNy41NDFjMTMuNjEsMTAuMTg1LDI3Ljc0LDE1LjI3LDQyLjM5OCwxNS4yN2gyNjMuNTIxYzEyLjM2NywwLDI0LjAyNi00LjE0MSwzNC45NzEtMTIuNDE2ICAgYzEwLjk0NC04LjI4MSwxOC4yMjctMTguNTA3LDIxLjgzNy0zMC42OTZsNzguNTExLTI1OC42NjJDNDc3LjQxMiwxNDEuNTEsNDc1LjcwMSwxMjkuMjM0LDQ2OC4wODMsMTE4LjM4NXogTTE2NC4zMSwxMTguOTU2ICAgbDUuOTk3LTE4LjI3NGMwLjc2LTIuNDc0LDIuMzI5LTQuNjE1LDQuNzA5LTYuNDIzYzIuMzgtMS44MDUsNC44MDgtMi43MTIsNy4yODItMi43MTJoMTczLjU4OWMyLjY2MywwLDQuNTY1LDAuOTAzLDUuNzA4LDIuNzEyICAgYzEuMTQsMS44MDksMS4zMzUsMy45NDksMC41NzUsNi40MjNsLTYuMDAyLDE4LjI3NGMtMC43NjQsMi40NzUtMi4zMjcsNC42MTEtNC43MTMsNi40MjRjLTIuMzgyLDEuODA1LTQuODA1LDIuNzA4LTcuMjc4LDIuNzA4ICAgSDE3MC41OTNjLTIuNjY2LDAtNC41NjgtMC45LTUuNzExLTIuNzA4QzE2My43NCwxMjMuNTY3LDE2My41NSwxMjEuNDMxLDE2NC4zMSwxMTguOTU2eiBNMTQwLjYxNSwxOTIuMDQ1bDUuOTk2LTE4LjI3MSAgIGMwLjc2LTIuNDc0LDIuMzMxLTQuNjE1LDQuNzA5LTYuNDIzYzIuMzgtMS44MDksNC44MDUtMi43MTIsNy4yODItMi43MTJoMTczLjU4M2MyLjY2NiwwLDQuNTcyLDAuOSw1LjcxMiwyLjcxMiAgIGMxLjE0LDEuODA5LDEuMzMxLDMuOTQ5LDAuNTY4LDYuNDIzbC01Ljk5NiwxOC4yNzFjLTAuNzU5LDIuNDc0LTIuMzMsNC42MTctNC43MDgsNi40MjNjLTIuMzgzLDEuODA5LTQuODA1LDIuNzEyLTcuMjgzLDIuNzEyICAgSDE0Ni44OTVjLTIuNjY0LDAtNC41NjctMC45LTUuNzA4LTIuNzEyQzE0MC4wNDMsMTk2LjY2MiwxMzkuODU0LDE5NC41MTksMTQwLjYxNSwxOTIuMDQ1eiIgZmlsbD0iI2UwOTQ3NiIvPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48L3N2Zz4=');
		add_submenu_page('wpcommission', 'Amazon Items', 'Amazon Items', 'manage_options', 'wpcommission-items', [$this, 'settings_items_page']);
		add_submenu_page('wpcommission', 'Blacklist', 'Blacklist', 'manage_options', 'wpcommission-blacklist', [$this, 'settings_blacklist_page']);
	}

	public function manually_add_item()
	{
		$redirect = $_POST['redirect'];
		$asins = wp_commission_get_all_asins($_POST['url']);
		if (!empty($asins)) {
			$this->client->pin_item($asins[0]);
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Added item.</p></div>");
		} else {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-error\"><p>Invalid Amazon item URL.</p></div>");
		}
		wp_redirect($redirect);
		exit;
	}

	private function set_pending_admin_notice($message)
	{
		set_transient(get_current_user_id() . '_pending_admin_notice', $message);
	}

	/**
	 * This also deletes the message, if it exists.
	 */
	private function get_pending_admin_notice()
	{
		$message = get_transient(get_current_user_id() . '_pending_admin_notice');
		if ($message) {
			delete_transient(get_current_user_id() . '_pending_admin_notice');
		}
		return $message;
	}

	public function pin_item()
	{
		$redirect = $_GET['redirect'];
		if ($this->client->pin_item($_GET['asin'])) {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Pinned item.</p></div>");
		} else {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-error\"><p>Failed to pinn item.</p></div>");
		}
		wp_redirect($redirect);
		exit;
	}

	public function unpin_item()
	{
		$redirect = $_GET['redirect'];
		if ($this->client->unpin_item($_GET['id'])) {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Unpinned item.</p></div>");
		} else {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-error\"><p>Failed to unpinn item.</p></div>");
		}
		wp_redirect($redirect);
		exit;
	}

	public function blacklist_item()
	{
		$redirect = $_GET['redirect'];
		if ($this->client->blacklist_item($_GET['asin'])) {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Blacklisted item.</p></div>");
		} else {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-error\"><p>Failed to blacklist item.</p></div>");
		}
		wp_redirect($redirect);
		exit;
	}

	public function unblacklist_item()
	{
		$redirect = $_GET['redirect'];
		if ($this->client->unblacklist_item($_GET['id'])) {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Unblacklisted item.</p></div>");
		} else {
			$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-error\"><p>Failed to unblacklist item.</p></div>");
		}
		wp_redirect($redirect);
		exit;
	}

	public function change_license_key()
	{
		$redirect = $_GET['redirect'];
		update_option('wp-commission-license-key', $_POST['license-key']);
		$this->set_pending_admin_notice("<div class=\"notice is-dismissible notice-success\"><p>Changed key.</p></div>");
		wp_redirect($redirect);
		exit;
	}

	public function settings_items_page()
	{
		$this->include_file('tmpl/settings-items.php');
	}

	public function settings_blacklist_page()
	{
		$this->include_file('tmpl/settings-blacklist.php');
	}

	public function settings_page()
	{
		$this->include_file('tmpl/settings.php');
	}

	public function associate_id_notice()
	{
		$this->include_file('tmpl/associate-id-notice.php');
	}

	public function ajax()
	{
		return wp_send_json_success();
	}

	public function transition_post_status($new_status, $old_status, $post)
	{
		$this->client->update_book_mentions($post);
	}

	public function wp_enqueue_scripts()
	{
		wp_enqueue_script('pl-owl-carousel', $this->get_url() . 'js/vendor/owlcarousel/owl.carousel.min.js', ['jquery'], '2.2.1', true);
		wp_enqueue_style('pl-owl-carousel', $this->get_url() . 'js/vendor/owlcarousel/assets/owl.carousel.min.css', [], '2.2.1');
		wp_enqueue_style('pl-owl-carousel-theme', $this->get_url() . 'js/vendor/owlcarousel/assets/owl.theme.default.min.css', [], '2.2.1');
	}

	public function wp_footer()
	{
		?>
        <style>
            .owl-carousel .owl-item img {
                width: auto;
            }

            .owl-carousel {
                clear: both;
            }

            .owl-carousel .owl-dots.disabled {
                display: block !important;
            }
        </style>
        <script>
            jQuery(function ($) {
                $('.owl-carousel').each(function (idx, elem) {
                    var atts = $(elem).data('atts');

                    var autoWidth = !!atts['height'];
                    var loop = atts['loop'] == 'true';

                    $(elem).owlCarousel({
                        items: 5,
                        slideBy: 5,
                        margin: 10,
                        dots: true,
                        autoWidth: autoWidth,
                        loop: loop
                    });

                    $(elem).find('.owl-item > *').each(function (idx, elem) {
                        var img = $(elem).find('img');
                        if (atts['height']) {
                            img.css('height', atts['height']+'px');
                        }
                        $(elem).width(img.css('width'));
                    });
                });
            });

			<?php if ($this->get_amazon_associate_id()) { ?>
            jQuery(document).ready(function () {
                function tagAllAmazonLinks() {
                    var re = 'https?://(www\\.)?amazon\\.com/([0-9A-Za-z_-]+/)?(dp|gp/product|exec/obidos/asin)/([A-Za-z0-9_-]+)';
                    var key = '<?= $this->get_amazon_associate_id() ?>';

                    jQuery('a').filter(function (_, l) {
                        return l.href.match(re);
                    }).each(function (_, l) {
                        l.href = l.href.replace(/(\?|&)tag=.+?($|&)/, '');
                        var separator = (l.href.indexOf('?') > -1) ? '&' : '?';
                        l.href += separator + 'tag=' + key;
                    });
                }

                function expandAmznToLinks() {
                    var expandAmazonToLinkPath = '<?= get_site_url(); ?>/wp-json/wp-commission/v1/expand';
                    var re = 'https?://(www\\.)?amzn\\.to/';
                    var matchingLinks = jQuery('a').filter(function (_, l) {
                        return l.href.match(re);
                    });
                    var urls = matchingLinks.map(function (_, l) {
                        return l.href;
                    }).toArray();

                    if (urls.length) {
                        jQuery.get(
                            expandAmazonToLinkPath,
                            {urls: urls.join(',')},
                            function (data) {
                                // First, replace short links with expanded versions.
                                jQuery.each(data.links, function (k, v) {
                                    matchingLinks.filter(function (_, l) {
                                        return l.href === k;
                                    }).each(function (_, l) {
                                        l.href = v;
                                    })
                                });

                                // Then tag all links.
                                tagAllAmazonLinks();
                            }
                        );
                    }
                }


                if (!window.disableWP) {
                    tagAllAmazonLinks();
                    expandAmznToLinks();
                }
            });
			<?php } ?>

        </script>
		<?php
	}

	public function no_rewrite()
	{
		return '<script>window.disableWP = true;</script>';
	}

	public function recent_books($atts)
	{
		$response = $this->client->get_recent_book_mentions_response($atts);
		if ($response == null || !$this->correctly_configured()) {
			return false;
		}
		$items = json_decode($response->getBody())->{'items'};

		if ($atts == null) {
			$atts = new ArrayObject();
		}

		$return = '<div data-atts="' . htmlspecialchars(json_encode($atts)) . '" class="owl-carousel owl-theme">';
		foreach ($items as $item) {
			$return .= "<div>";
			$return .= "<a target=\"_blank\" href='" . $item->url . "'><img src='" . $item->large_image_url . "'/></a>";
			$return .= "</div>";
		}
		$return .= '</div>';
		return $return;
	}
}