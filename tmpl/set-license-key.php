<?php defined('WPINC') or die; ?>

<p><?php if ($current_license) { ?>
        Current WP Commission License Key is <?= $current_license ?>.
	<?php } else { ?>
        No WP Commission License Key is currently set.
	<?php } ?></p>

<p>Make sure to <a target="_blank" href="https://wpcommission.com/license">update your license in WP Commission</a> to accept the site
    <strong><?= $this->client->get_home_url_no_protocol() ?></strong>.</p>

<form method="post" action="<?= get_admin_url(null, 'admin-post.php?action=change_license_key&redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>">
    <input name="license-key" type="text" value="<?= $current_license ?>"
           placeholder="Enter a WP Commission License Key"
           size="40"/> <?php submit_button('Set License Key', 'secondary', 'set-license-key', false); ?>
</form>
