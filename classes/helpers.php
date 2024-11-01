<?php

function wp_commission_get_asin_url_regex()
{
	return '#https?://(www\.)?amazon\.com/([\w-]+/)?(dp|gp/product|exec/obidos/asin)/(?<asin>[A-Za-z0-9_-]+)#';
}

function wp_commission_get_all_asins($content)
{
	$found = preg_match_all(wp_commission_get_asin_url_regex(), $content, $matches);

	if ($found) {
		return array_values(array_unique($matches['asin']));
	} else {
		return [];
	}
}

function wp_commission_get_sync_post_types()
{
	$custom_post_types = array_values(get_post_types(['_builtin' => false]));
	return array_merge($custom_post_types, array('post', 'page'));
}

function wp_commission_get_total_posts()
{
	$total_posts = 0;

	foreach (wp_commission_get_sync_post_types() as $t) {
		$total_posts += (int) wp_count_posts($t)->publish;
	}

	return $total_posts;
}

/**
 * @param $post The post or page to be summarized.
 * @return An array ready to be POSTed as JSON, or null.
 */
function wp_commission_get_page_info($post)
{
	return [
		'table' => $post->post_type,
		'wp_id' => $post->ID,
		'wp_author_id' => (int)$post->post_author,
		'wp_tags' => wp_get_post_tags($post->ID, array('fields' => 'ids')),
		'wp_categories' => wp_get_post_tags($post->ID, array('fields' => 'ids')),
		'wp_modified_at' => $post->post_modified_gmt,
		'asins' => wp_commission_get_all_asins($post->post_content),
		'status' => $post->post_status,
	];
}

function wp_commission_endsWith($haystack, $needle)
{
	$length = strlen($needle);

	return $length === 0 ||
		(substr($haystack, -$length) === $needle);
}