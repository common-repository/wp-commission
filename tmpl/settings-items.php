<?php defined('WPINC') or die; ?>

<style>
    div.item {
        position: relative;
        margin-bottom: 25px;
    }

    div.item div.img-wrapper {
        position: relative;
        height: 100%;
        float: left;
        height: 150px;
    }

    div.item img {
        max-width: 100px;
        max-height: 150px;
        margin-right: 20px;
    }

    div.item div.clear {
        clear: both;
    }

    div.item div.item-wrapper {
        /* position: absolute;
        top: 0;
        left: 120px; */
    }

    div.item div.item-wrapper h3 {
        margin-top: 0;
    }

    #manual-form {
        margin-top: 15px;
    }

    #manual-form input {
        width: 50%;
        min-width: 300px;
    }
</style>

<div class="wrap">
    <h1>Amazon Items</h1>

	<?php if ($this->correctly_configured()) { ?>

		<?php if ($this->get_plan_type() == 'bloggers') { ?>
            <p>Because you're using WP Commission for Bloggers, you will not be able to pin, blacklist, or manually add
                items. Please <a href="https://wpcommission.com/license" target="_blank">upgrade to WP Commission for
                    Pros or WP Commission for Publishers</a> to use those features.</p>
		<?php } ?>

		<?php if ($this->get_plan_type() !== 'bloggers') { ?>
            <h2>Manually add an item</h2>

            <p>Insert an Amazon URL, e.g.
                <code>https://www.amazon.com/Lord-Rings-50th-Anniversary-Vol/dp/0618640150</code>.
            </p>

            <form id="manual-form" method="POST"
                  action="<?= get_admin_url(null, 'admin-post.php?action=manually_add_item') ?>">
                <input type="text" name="url" required/>
                <input type="hidden" name="redirect"
                       value="<?= get_admin_url(null, 'admin.php?page=wpcommission-items') ?>"/>
                <button type="submit">Submit</button>
            </form>
		<?php } ?>

        <h2>Items</h2>

		<?php if ($this->correctly_configured()) {
			$response = $this->client->get_recent_book_mentions_response(['limit' => 20], true);
			if ($response == null) {
				return;
			}
			$body = json_decode($response->getBody());
		}

		if (empty($body->{'items'})) { ?>

            <p>Looks like you don't have links to any Amazon Items. Try syncing items on the WP Commission page.</p>

            <p>If you removed this site in the past on the WP Commission license page, you'll need to re-sync posts and
                pages.</p>

		<?php } else { ?>

            <p>Showing <?= count($body->{'items'}) ?> of <?= $body->{'total'} ?> indexed Amazon items. This list ignores
                blacklisted Amazon Items.</p>

            <div class="items-wrapper">
				<?php foreach ((array)$body->{'items'} as $item) { ?>
                    <div class="item">
                        <div class="img-wrapper">
                            <img src="<?= esc_url($item->large_image_url) ?>"/>
                        </div>
                        <div class="item-wrapper">
                            <h3><?= $item->name ?></h3>
                            <p>ASIN: <a target="_blank"
                                        href="<?= esc_url($item->url); ?>"><?= esc_html($item->asin); ?></a>
                            </p>
							<?php if (!empty($item->page_wp_ids)) {
								echo "<p>Referenced in ";
								foreach ($item->page_wp_ids as $page_id) {
									$sep = "";
									if (next($item->page_wp_ids)) {
										$sep = ", ";
									} ?>
                                    <a target="_blank"
                                       href="<?= get_page_link($page_id) ?>"><?= get_page($page_id)->post_title ?></a><?= $sep ?>
									<?php
								}
								echo "</p>";
							} else { ?>
                                <p>This item is not referenced in any posts. Unpinning it will remove it from the list
                                    of items until referenced in a post or manually re-added.</p>
							<?php } ?>

							<?php if ($this->get_plan_type() !== 'bloggers') { ?>
                                <p>
									<?php if ($item->pinned_item_id) { ?>
                                        <a href="<?= get_admin_url(null, 'admin-post.php?action=unpin_item&id=' . $item->pinned_item_id . '&redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Unpin
                                            item</a>
									<?php } else { ?>
                                        <a href="<?= get_admin_url(null, 'admin-post.php?action=pin_item&asin=' . $item->asin . '&redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Pin
                                            item</a>
									<?php } ?>

                                    &middot;

                                    <a href="<?= get_admin_url(null, 'admin-post.php?action=blacklist_item&asin=' . $item->asin . '&redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Blacklist
                                        item</a>
                                </p>
							<?php } ?>

                            <div class="clear"></div>
                        </div>
                    </div>
				<?php } ?>
            </div>

		<?php } ?>

	<?php } else { ?>

        <p>Please double-check your configuration.</p>

	<?php } ?>
</div>