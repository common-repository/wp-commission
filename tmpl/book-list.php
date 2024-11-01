<?php defined('WPINC') or die; ?>

<h2>Items</h2>

<?php if (empty($body->{'items'})) { ?>

    <p>Looks like you don't have links to any Amazon Items. Try syncing items above.</p>

    <p>If you removed this site in the past on the WP Commission license page, you'll need to re-sync posts and pages.</p>

<?php } else { ?>

    <p>Showing <?= count($body->{'items'}) ?> of <?= $body->{'total'} ?> indexed Amazon items.</p>

    <ul class="book-list" data-type="<?= esc_attr($type) ?>">
		<?php foreach ((array)$body->{'items'} as $item) { ?>
            <li data-asin="<?= esc_attr($item->asin) ?>">
                <a href="<?= esc_url($item->url); ?>">[<?= esc_html($item->asin); ?>] <?= esc_html($item->name); ?></a> (<?= implode(", ", $item->page_wp_ids) ?>)
            </li>
		<?php } ?>
    </ul>

<?php } ?>