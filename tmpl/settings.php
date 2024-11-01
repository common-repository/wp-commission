<?php defined('WPINC') or die; ?>

<div class="wrap">
    <h1>WP Commission</h1>

    <h2>License Key</h2>

	<?php $this->include_file('tmpl/set-license-key.php', [
		'current_license' => get_option('wp-commission-license-key', ''),
	]); ?>

	<?php if ($this->correctly_configured()) { ?>
        <h2>Sync with WP Commission</h2>
		<?php $this->include_file('tmpl/sync.php') ?>

        <h2>Documentation</h2>
        <p>WP Commission is easy: links to Amazon products automatically get tagged with <a href="https://wpcommission.com/license" target="_blank">your Affiliate ID</a>, and you can insert carousels with the <code>[wpcommission]</code> shortcode. Check out <a href="https://wpcommission.com/documentation" target="_blank">the documentation</a> for all other questions &mdash; or shoot us an email if you don't see what you're looking for. </p>

        <?php if ($this->get_plan_type() == 'bloggers') { ?>
            <h2>Upgrade for More</h2>
            <p><a href="https://wpcommission.com/license" target="_blank">Upgrade to WP Commission for Pros or WP Commission for Publishers</a> to be able to pin and blacklist items, add additional sites, and make named lists.</p>
        <?php } ?>
	<?php } ?>
</div>
