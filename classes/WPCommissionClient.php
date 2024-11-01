<?php
defined('WPINC') or die;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Kevinrob\GuzzleCache\CacheMiddleware;

class WPCommissionClient
{
	public function __construct()
	{
		$stack = HandlerStack::create();
		$stack->push(
			new CacheMiddleware(
				new WPCommissionRelaxedPublicCacheStrategy(
					new WPCommissionTransientStorage()
				)
			),
			'cache'
		);
		$this->client = new Client([
			'handler' => $stack,
			'headers' => [
				'X-WP-COMMISSION-LICENSE' => get_option('wp-commission-license-key', ''),
				'X-WP-COMMISSION-WP-URL' => $this->get_home_url_no_protocol(),
				'Content-Type' => 'application/json',
				'X-WP-COMMISSION-PLUGIN-VERSION' => $GLOBALS['WP_COMMISSION_VERSION'], // depends on initialization code in entry point
			],
			'http_errors' => false,
		]);
	}

	/**
	 * @param $pages
	 * @return bool
	 */
	public function sync($pages)
	{
		try {
			$this->client->request('PATCH', $this->get_application_server() . '/api/v1/pages', [
				'json' => $pages,
				'timeout' => 10, // timeout after 10 seconds
			]);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Scans a post for book mentions and posts any updates to
	 * the application server.
	 *
	 * @param int|WP_Post $post The post being scanned
	 * @return null
	 */
	public function update_book_mentions($post)
	{
		$post = get_post($post);
		if ($post) {
			try {
				$this->client->request('PATCH', $this->get_application_server() . '/api/v1/pages', [
					'timeout' => 3, // timeout after 3 seconds
					'json' => wp_commission_get_page_info($post)
				]);
			} catch (Exception $e) {
				// Do nothing.
			}
		}
	}

	/**
	 * @param $atts
	 * @return null|\Psr\Http\Message\ResponseInterface
	 */
	public function get_recent_book_mentions_response($atts, $skip_cache = false)
	{
		$query_params = [];

		if ($skip_cache) {
			$query_params['cache_buster'] = uniqid();
		}

		if (!$atts) {
			$atts = array();
		}

		if (array_key_exists('limit', $atts)) {
			$query_params['limit'] = $atts['limit'];
		} else {
			$query_params['limit'] = 10;
		}
		if (array_key_exists('author', $atts)) {
			$query_params['author_id'] = $atts['author'];
		}
		if (array_key_exists('page_type', $atts)) {
			$query_params['page_table'] = $atts['page_type'];
		}
		if (array_key_exists('page_id', $atts)) {
			$query_params['page_id'] = $atts['page_id'];
		}
		if (array_key_exists('tag', $atts)) {
			$query_params['page_tag'] = $atts['tag'];
		}
		if (array_key_exists('category', $atts)) {
			$query_params['page_category'] = $atts['category'];
		}
		if (array_key_exists('amazon_category', $atts)) {
			$query_params['item_category'] = $atts['amazon_category'];
		}

		$response = null;

		try {
			$response = $this->client->request('GET', $this->get_application_server() . '/api/v1/items', [
				'query' => $query_params,
				'timeout' => 3 // timeout after 3 seconds
			]);
		} catch (Exception $e) {
			// Do nothing
		}

		return $response;
	}

	public function get_blacklisted_items()
	{
		try {
			return $this->client->request('GET', $this->get_application_server() . '/api/v1/blacklisted-items', [
				'timeout' => 3 // timeout after 3 seconds
			]);
		} catch (Exception $e) {
			// Do nothing.
			return false;
		}
	}

	public function blacklist_item($asin)
	{
		try {
			return $this->client->request('POST', $this->get_application_server() . '/api/v1/blacklisted-items', [
				'timeout' => 3, // timeout after 3 seconds
				'json' => [
					'asin' => $asin
				]
			]);
		} catch (Exception $e) {
			// Do nothing.
			return false;
		}
	}

	public function unblacklist_item($blacklist_item_id)
	{
		try {
			return $this->client->request('DELETE', $this->get_application_server() . '/api/v1/blacklisted-items/' . $blacklist_item_id . '/', [
				'timeout' => 3 // timeout after 3 seconds
			]);
		} catch (Exception $e) {
			// Do nothing.
			return false;
		}
	}

	public function pin_item($asin)
	{
		try {
			return $this->client->request('POST', $this->get_application_server() . '/api/v1/pinned-items', [
				'timeout' => 3, // timeout after 3 seconds
				'json' => [
					'asin' => $asin
				]
			]);
		} catch (Exception $e) {
			// Do nothing.
			return false;
		}
	}

	public function unpin_item($pinned_item_id)
	{
		try {
			return $this->client->request('DELETE', $this->get_application_server() . '/api/v1/pinned-items/' . $pinned_item_id . '/', [
				'timeout' => 3, // timeout after 3 seconds
			]);
		} catch (Exception $e) {
			// Do nothing.
			return false;
		}
	}

	/**
	 * @return string
	 */
	private function get_application_server()
	{
		$is_devlocalhost = strpos(get_home_url(), 'devlocalhost');
		if ( $is_devlocalhost ) {
			return 'http://localhost:8000';
		}
		return 'https://wpcommission.com';
	}

	/**
	 * @return bool|mixed|\Psr\Http\Message\ResponseInterface
	 */
	public function make_check_request()
	{
		try {
			return $this->client->request('GET', $this->get_application_server() . '/api/v1/check', [
				'timeout' => 3 // timeout after 3 seconds
			]);
		} catch (Exception $e) {
			return false;
		}
	}

	public function expand($urls) {
		try {
			return $this->client->request('GET', $this->get_application_server() . '/api/v1/expand?urls=' . $urls, [
				'timeout' => 10, // timeout after 10 seconds
			]);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function get_home_url_no_protocol()
	{
		return str_replace(array('http://', 'https://'), '', get_home_url());
	}
}