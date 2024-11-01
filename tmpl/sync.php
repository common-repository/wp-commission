<p>Once you enter a valid license key and <a href="https://wpcommission.com/license" target="_blank">update WP Commission to use this WordPress installation</a>, press the button below to scrape all Amazon links from your posts and pages so we can tag them with your affiliate ID.</p>

<p>This operation may take a moment.</p>

<p id="indexing-progress" class="hidden">Indexed <span id="posts-done">0</span> of <span id="posts-total">0</span> posts.</p>

<script>
    jQuery(document).ready(function($) {
        var button = $('#sync-button');
        var pageSize = 250;
        var basePath = '<?= get_site_url(); ?>/wp-json/wp-commission/v1/sync-batch';

        var total = <?= wp_commission_get_total_posts() ?>;
        var doneElem = $('#posts-done');
        var totalElem = $('#posts-total');

        var indexingProgress = $('#indexing-progress');

        totalElem.text(total);

        button.click(function() {
            doneElem.text(0);

            button.attr('disabled', 'true');
            indexingProgress.removeClass('hidden');

            function makePost(offset) {
                var path = basePath + '?limit=' + pageSize + '&offset=' + offset;
                return $.ajax(path, {
                    method: 'POST',
                    success: function(resp) {
                       var newCount = offset + resp['num_posts'];
                       doneElem.text(Math.min(newCount, total)); // in case of overflow

                       if (newCount < total) {
                           return makePost(offset + pageSize);
                       }
                   },
                    error: function(xhr) {
                       indexingProgress.text('Sync failed! Please refresh the page and try again.');
                       return;
                    }
                });
            }

            makePost(0);
        });
    });
</script>

<?php submit_button('Sync', 'primary', 'sync', false, ['id' => 'sync-button']); ?>
