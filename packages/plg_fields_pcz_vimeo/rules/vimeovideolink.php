<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\Registry\Registry;
use Joomla\Uri\Uri;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Rule\UrlRule;

defined('_JEXEC') or die;

// Require helper
JLoader::register('PlgFieldsPcz_VimeoHelper', __DIR__ . '/../helper.php');

/**
 * JFormVimeolink for plg_fields_pcz_vimeo to make sure value is valid Vimeo video link
 *
 * @since  1.0.0-alpha.1
 */
class JFormRuleVimeoVideoLink extends UrlRule
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

		$uri = new Uri($value);

		// Scheme
		if ($uri->getScheme() !== 'https')
		{
			return false;
		}

		// Host
		if ($uri->getHost() !== 'vimeo.com')
		{
			return false;
		}

		// Port
		if ($uri->getPort())
		{
			return false;
		}

		// User
		if ($uri->getUser())
		{
			return false;
		}

		// Password
		if ($uri->getPass())
		{
			return false;
		}

		// Path
		if (!$uri->getPath())
		{
			return false;
		}

		// Path params
		$pathParams = PlgFieldsPcz_VimeoHelper::getVimeoPathParams($uri->getPath());

		// Failed to parse format
		if (!is_int($pathParams['vimeoId']))
		{
			return false;
		}

		// Query
		if ($uri->getQuery())
		{
			return false;
		}

		// Fragment
		if ($uri->getFragment())
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

		$uri = new Uri($value);

		// Extract Vimeo ID + Unlisted hash
		$pathParams = PlgFieldsPcz_VimeoHelper::getVimeoPathParams($uri->getPath());

		// Failed to parse format
		if (!is_int($pathParams['vimeoId']))
		{
			return null;
		}

		// Create path with removing optional unlisted hash when null
		$normalizedUrlPath = sprintf('/%s', implode('/', array_filter($pathParams)));

		// Replace path
		$uri->setPath($normalizedUrlPath);

		// Drop query and fragment
		$uri->setQuery(null);
		$uri->setFragment(null);

		return (string) $uri;
	}
}
