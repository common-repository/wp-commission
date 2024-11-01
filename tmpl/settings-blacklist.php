<style>
    div.item {
        position: relative;
        margin-bottom: 10px;
    }

    div.item img {
        float: left;
        max-width: 100px;
        margin-right: 20px;
    }

    div.item div.clear {
        clear: both;
    }
</style>

<div class="wrap">
    <h1>Blacklisted Items</h1>

	<?php if ($this->correctly_configured()) { ?>

		<?php if ($this->get_plan_type() == 'bloggers') { ?>

            <p>Please <a href="https://wpcommission.com/license" target="_blank">upgrade to WP Commission for Pros or WP Commission for Publishers</a> to use the blacklist.</p>

		<?php } else { ?>

            <p>These Amazon items will <em>not</em> show up on carousels (though their links will still be rewritten to
                include an affiliate tag).</p>

			<?php if ($this->correctly_configured()) {
				$response = $this->client->get_blacklisted_items();
				if ($response == null) {
					return;
				}
				$body = json_decode($response->getBody());
			}

			if (empty($body->{'items'})) { ?>

                <p>Looks like you don't have any blacklisted Amazon Items.</p>

			<?php } else { ?>

                <p>You have <?= count($body->{'items'}) ?> blacklisted items.</p>

                <div class="item-wrapper">
					<?php foreach ((array)$body->{'items'} as $item_wrapper) { ?>
                        <div class="item">
							<?php $item = $item_wrapper->{'item'}; ?>
                            <img src="<?= esc_url($item->large_image_url) ?>"/>
                            <h3><?= $item->name ?></h3>
                            <p>ASIN: <a target="_blank"
                                        href="<?= esc_url($item->url); ?>"><?= esc_html($item->asin); ?></a>
                            </p>
							<?php if (!empty($item->page_wp_ids)) {
								echo "<p>Referenced in ";
								foreach ($item->page_wp_ids as $page_id) {
									?>
                                    <a target="_blank"
                                       href="<?= get_page_link($page_id) ?>"><?= get_page($page_id)->post_title ?></a>
									<?php
									if (next($item->page_wp_ids)) {
										echo ", ";
									}
								}
								echo "</p>";
							} ?>
                            <p>
                                <a href="<?= get_admin_url(null, 'admin-post.php?action=unblacklist_item&id=' . $item_wrapper->id . '&redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Unblacklist
                                    item</a></p>
                            <div class="clear"></div>
                        </div>
					<?php } ?>
                </div>

			<?php } ?>

		<? } ?>

	<?php } else { ?>

        <p>Please double-check your configuration.</p>

	<?php } ?>
</div>