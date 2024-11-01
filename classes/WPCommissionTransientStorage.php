<?php
defined('WPINC') or die;

use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;

class WPCommissionTransientStorage implements CacheStorageInterface
{
	/**
	 * @param string $key
	 *
	 * @return CacheEntry|null the data or false
	 */
	public function fetch($key)
	{
		try {
			$cache = unserialize(get_transient($key));
			if ($cache instanceof CacheEntry) {
				return $cache;
			}
		} catch (\Exception $ignored) {
			// Don't fail if we can't load it
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param CacheEntry $data
	 *
	 * @return bool
	 */
	public function save($key, CacheEntry $data)
	{
		try {
			return set_transient($key, serialize($data), $data->getTTL());
		} catch (\Exception $ignored) {
			// Don't fail if we can't save it
		}

		return false;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function delete($key)
	{
		try {
			return delete_transient($key);
		} catch (\Exception $ignored) {
			// Don't fail if we can't delete it
		}

		return false;
	}
}