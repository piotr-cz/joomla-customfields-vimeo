<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\Registry\Registry;
use Joomla\Uri\Uri;
use Joomla\URI\UriInterface;

/**
 * Helper for plg_fields_pcz_vimeo
 *
 * @since  1.0.0-alpha.1
 */
class PlgFieldsPcz_VimeoHelper
{
	public const VIMEO_ID = 'vimeoId';
	public const VIMEO_HASH = 'unlistedHash';

	/**
	 * Get Vimeo ID from URL
	 *
	 * @param   string  $url  Vimeo URL, ie 'https://vimeo.com/286898202'
	 * @return  integer|null
	 */
	public static function getVimeoId(string $url): ?int
	{
		if ($url == '')
		{
			return null;
		}

		$pathParams = static::getVimeoPathParams($url);

		return $pathParams[static::VIMEO_ID];
	}

	/**
	 * Get Vimeo embeded player URL
	 *
	 * @param   string                     $url          Vimeo URL
	 * @param   \Joomla\Registry\Registry  $fieldParams  Field parameters
	 * @return  \Joomla\Uri\UriInterface
	 */
	public static function getEmbedSrc(string $url, Registry $fieldParams): UriInterface
	{
		$pathParams = static::getVimeoPathParams($url);

		$vimeoParams = static::getVimeoParams($fieldParams, $pathParams[static::VIMEO_HASH]);

		$vimeoSrc = new URI(sprintf('https://player.vimeo.com/video/%s', $pathParams[static::VIMEO_ID]));
		$vimeoSrc->setQuery($vimeoParams);

		return $vimeoSrc;
	}

	/**
	 * Get Vimeo URL path parameters by detecting Vimeo URL scheme
	 * @see https://developer.vimeo.com/api/oembed/videos#table-1
	 *
	 * @param   string  $url  URL
	 * @return  array
	 */
	public static function getVimeoPathParams(string $url): array
	{
		$urlPath = parse_url($url, PHP_URL_PATH);

		list ($firstSegment) = explode('/', ltrim($urlPath, '/'), 2);

		switch ($firstSegment)
		{
			// Showcase
			case 'album':
				list ($albumId, $vimeoId, $unlistedHash) = sscanf($urlPath, '/album/%d/video/%d/%s');
				break;

			// Channel
			case 'channels':
				list ($channelId, $vimeoId, $unlistedHash) = sscanf($urlPath, '/channels/%d/%d/%s');
				break;

			// Group
			case 'groups':
				list ($groupId, $vimeoId, $unlistedHash) = sscanf($urlPath, '/groups/%d/videos/%d/%s');
				break;

			// On Demand video
			case 'ondemand':
				list ($ondemandId, $vimeoId, $unlistedHash) = sscanf($urlPath, '/ondemand/%d/%d/%s');
				break;

			// A regular video
			default:
				list ($vimeoId, $unlistedHash) = sscanf($urlPath, '/%d/%s');
				break;
		}

		return [
			static::VIMEO_ID => $vimeoId,
			static::VIMEO_HASH => $unlistedHash,
		];
	}

	/**
	 * Get Vimeo parameters that may be used as video src query params
	 *
	 * @param   \Joomla\Registry\Registry  $fieldParams  Field parameters
	 * @param   string                     $vimeoHash    Vimeo hash
	 * @return  array
	 *
	 * @see [Vimeo: Using Player Parameters]{@link https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameters
	 */
	protected static function getVimeoParams(Registry $fieldParams, ?string $vimeoHash = null): array
	{
		// Default Vimeo player parameters
		$defaultVimeoParams = [
			'h'           => null,
			'autopause'   => 1,
			'autoplay'    => 0,
			'background'  => 0,
			'byline'      => 1,
			'color'       => '00adef',
			'controls'    => 1,
			'dnt'         => 0,
			'loop'        => 0,
			'muted'       => 0,
			'pip'         => 0,
			'playsinline' => 1,
			'portrait'    => 1,
			'quality'     => 'auto',
			'speed'       => 0,
			'#t'          => '0m',
			'texttrack'   => 0,
			'title'       => 1,
			'transparent' => 1,
		];

		// User defined Vimeo player parameters
		$vimeoParams = [
			'h'           => $vimeoHash,
			'autopause'   => (int) $fieldParams->get('vp_autopause', 1),
			'autoplay'    => (int) $fieldParams->get('vp_autoplay', 0),
			'background'  => (int) $fieldParams->get('vp_background', 0),
			'byline'      => (int) $fieldParams->get('vp_byline', 1),
			'color'       => ltrim((string) $fieldParams->get('vp_color', '#00adef'), '#'),
			'controls'    => (int) $fieldParams->get('vp_controls', 1),
			'dnt'         => (int) $fieldParams->get('vp_dnt', 0),
			'loop'        => (int) $fieldParams->get('vp_loop', 0),
			'muted'       => (int) $fieldParams->get('vp_muted', 0),
			'pip'         => (int) $fieldParams->get('vp_pip', 0),
			'playsinline' => (int) $fieldParams->get('vp_playsinline', 1),
			'portrait'    => (int) $fieldParams->get('vp_portrait', 1),
			'quality'     => (string) $fieldParams->get('vp_quality', 'auto'),
			'speed'       => (int) $fieldParams->get('vp_speed', 0),
			'#t'          => (string) $fieldParams->get('vp_t', '0m'),
			'texttrack'   => (int) $fieldParams->get('vp_texttrack', 0),
			'title'       => (int) $fieldParams->get('vp_title', 1),
			'transparent' => (int) $fieldParams->get('vp_transparent', 1),
		];

		// Remove query params when same as defaults
		return array_filter(
			$vimeoParams,
			function ($value, string $key) use ($defaultVimeoParams) {
				return !array_key_exists($key, $defaultVimeoParams) || $defaultVimeoParams[$key] !== $value;
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
