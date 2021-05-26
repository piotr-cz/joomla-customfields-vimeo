<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Rule\UrlRule;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * JFormVimeolink for plg_fields_pcz_vimeo to make sure value is valid Vimeo video link
 *
 * @since  1.0.0-alpha.1
 */
class JFormRuleVimeovideolink extends UrlRule
{
	/**
	 * @inheritdoc
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 * @param   Registry           $input    An optional Registry object with the entire data set to validate against the entire form.
	 * @param   Form               $form     The form object for which the field is being tested.
	 *
	 * @return  boolean  True if the value is valid, false otherwise.
	 */
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		// Parent tests
		if (!parent::test($element, $value, $group, $input, $form))
		{
			return false;
		}

		// If the field is empty and not required, the field is valid.
		$required = ((string) $element['required'] === 'true' || (string) $element['required'] === 'required');

		if (!$required && empty($value))
		{
			return true;
		}

		$components = parse_url($value);

		// Scheme
		if ($components['scheme'] !== 'https')
		{
			return false;
		}

		// Host
		if ($components['host'] !== 'vimeo.com')
		{
			return false;
		}

		// Port
		if (isset($components['port']))
		{
			return false;
		}

		// User
		if (isset($components['user']))
		{
			return false;
		}

		// Password
		if (isset($components['pass']))
		{
			return false;
		}

		// Path
		if (empty($components['path']))
		{
			return false;
		}

		// Path params
		$pathParams = static::getVimeoPathParams($components['path']);

		// Failed to parse format
		if (is_null($pathParams['vimeoId']))
		{
			return false;
		}

		// Non integer
		if (!is_int($pathParams['vimeoId']))
		{
			return false;
		}

		// Query
		if (isset($components['query']))
		{
			return false;
		}

		// Fragment
		if (isset($components['fragment']))
		{
			return false;
		}

		return true;
	}

	/**
	 * Custom filter/ sanitizer to normalize any Vimeo url to Regular vimeo video link
	 *
	 * @example
	 * ```xml
	 * <field filter="JFormRuleVimeovideolink::filterVimeoVideoLink" />
	 * ```
	 *
	 * Class must be loaded manually bucausse Joomla Form doesn't to so
	 * @example
	 * ```php
	 * // Require helper for filter functions called by JForm.
	 * JLoader::register('JFormRuleVimeovideolink', __DIR__ . '/rules/vimeovideolink.php');
	 * ```
	 *
	 * @param   string  $value  The string to filter
	 * @return  string|null  The filtered string which will be set as a value or null on failure
	 *
	 * @see https://developer.vimeo.com/api/oembed/videos#table-1
	 */
	public static function filterVimeoVideoLink(string $value): ?string
	{
		if (empty($value))
		{
			return $value;
		}

		// Extract Vimeo ID + Unlisted hash
		$urlPath = parse_url($value, PHP_URL_PATH);
		$pathParams = static::getVimeoPathParams($urlPath);

		// Failed to parse format
		if (is_null($pathParams['vimeoId']))
		{
			return null;
		}

		// Non integer
		if (!is_int($pathParams['vimeoId']))
		{
			return null;
		}

		// Create path with removing optional unlisted hash when null
		$normalizedUrlPath = sprintf('/%s', implode('/', array_filter($pathParams)));

		// Replace path
		return str_replace($urlPath, $normalizedUrlPath, $value);
	}

	/**
	 * Get Vimeo URL path parameters by detecting Vimeo URL scheme
	 * @see https://developer.vimeo.com/api/oembed/videos#table-1
	 *
	 * @param   string  $urlPath  URL path component
	 * @return  array
	 */
	protected static function getVimeoPathParams(string $urlPath): array
	{
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
			'vimeoId' => $vimeoId,
			'unlistedHash' => $unlistedHash,
		];
	}
}
