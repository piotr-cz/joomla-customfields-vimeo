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

		if (empty($components['path']))
		{
			return false;
		}

		/*
		 * Parse URL by detecting URL scheme
		 * @see https://developer.vimeo.com/api/oembed/videos#table-1
		 */
		$urlPath = trim($components['path'], '/');

		list ($firstSegment) = explode('/', $urlPath, 2);

		switch ($firstSegment)
		{
			// Showcase
			case 'album':
				list ($albumId, $vimeoId, $unlistedHash) = sscanf($urlPath, 'album/%d/video/%d/%s');
				break;

			// Channel
			case 'channels':
				list ($channelId, $vimeoId, $unlistedHash) = sscanf($urlPath, 'channels/%d/%d/%s');
				break;

			// Group
			case 'groups':
				list ($groupId, $vimeoId, $unlistedHash) = sscanf($urlPath, 'groups/%d/videos/%d/%s');
				break;

			// On Demand video
			case 'ondemand':
				list ($ondemandId, $vimeoId, $unlistedHash) = sscanf($urlPath, 'ondemand/%d/%d/%s');
				break;

			// A regular video
			default:
				list ($vimeoId, $unlistedHash) = sscanf($urlPath, '%d/%s');
				break;
		}

		// Failed to parse format
		if (is_null($vimeoId))
		{
			return false;
		}

		// Non integer
		if (!is_int($vimeoId))
		{
			return false;
		}

		return true;
	}
}
