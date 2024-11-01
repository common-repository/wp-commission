<?php

function wp_commission_get_post_metadata(WP_REST_Request $request)
{
	return [
		'num_posts' => wp_commission_get_total_posts(),
	];
}

function wp_commission_expand(WP_REST_Request $request)
{
	$resp = (new WPCommissionClient())->expand($request['urls']);
	if (!$resp) {
		return new WP_Error('expand_failed', 'Expand failed', ['status' => 500]);
	}
	return json_decode($resp->getBody()->getContents());
}

function wp_commission_sync_batch(WP_REST_Request $request)
{
	$posts = (new WP_Query([
		'post_type' => wp_commission_get_sync_post_types(),
		'posts_status' => 'publish',
		'offset' => $request['offset'],
		'posts_per_page' => $request['limit'],
		'order' => 'ASC',
		'orderby' => 'ID',
	]))->posts;

	$last_id = null;
	if (!empty($posts)) {
		$last_id = end($posts)->ID;
		$synced = (new WPCommissionClient())->sync(array_map('wp_commission_get_page_info', $posts));
		if (!$synced) {
			return new WP_Error('sync_failed', 'Sync failed', ['status' => 500]);
		}
	}

	return [
		'last_id' => $last_id,
		'num_posts' => count($posts),
	];
}