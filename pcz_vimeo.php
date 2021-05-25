<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Joomla 4.x
 * use Joomla\CMS\Form\Form;
 * use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die;

// Joomla 3.x & 4.0
JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);

/**
 * Fields Vimeo Plugin
 * Available Site Events:
 * - onCustomFieldsBeforePrepareField
 * - onCustomFieldsPrepareField
 * - onCustomFieldsAfterPrepareField
 * - onAfterDispatch
 * - onBeforeCompilehead
 * - onAfterRender
 *
 * @since  1.0.0-alpha.1
 */
class PlgFieldsPcz_Vimeo extends FieldsPlugin
{
	/**
	 * @var  boolean
	 *
	 * @inheritdoc
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var \Joomla\CMS\Application\CMSApplication
	 */
	protected $app = null;

	/**
	 * @inheritdoc
	 *
	 * @param   stdClass    $field   The field.
	 * @param   DOMElement  $parent  The field node parent.
	 * @param   JForm       $form    The form.
	 * @return  DOMElement|null
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form): ?DOMElement
	{
		/** @var \DOMElement */
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		if (!$fieldNode)
		{
			return $fieldNode;
		}

		/*
		 * Set field type, filter and validation rule
		 * @see https://docs.joomla.org/Special:MyLanguage/J3.x:Adding_custom_fields/Parameters_for_all_Custom_Fields
		 * @see administrator/components/com_fields/src/Plugin/FieldsPlugin.php
		 */
		$fieldNode->setAttribute('type', 'url');
		$fieldNode->setAttribute('filter', 'url');
		$fieldNode->setAttribute('validate', 'Vimeovideolink');

		// Invalid field: Invalid Video link format
		// $fieldNode->setAttribute('message', 'PLG_FIELDS_PCZ_VIMEO_VALUE_VIDEO_LINK_FIELD_INVALID');

		// Set field options if not filled in admin

		// Label
		if (!$fieldNode->getAttribute('label'))
		{
			$fieldNode->setAttribute('label', 'PLG_FIELDS_PCZ_VIMEO_VALUE_VIDEO_LINK_LABEL');
		}

		// Description
		if (!$fieldNode->getAttribute('description'))
		{
			$fieldNode->setAttribute('description', 'PLG_FIELDS_PCZ_VIMEO_VALUE_VIDEO_LINK_DESC');
		}

		// Placeholder
		if (!$fieldNode->getAttribute('hint'))
		{
			$fieldNode->setAttribute('hint', 'https://vimeo.com/524933864');
		}

		return $fieldNode;
	}

	/**
	 * Get Vimeo ID from URL
	 *
	 * @param   string  $fieldValue  Vimeo ID or Vimeo URL, ie 'https://vimeo.com/286898202'
	 * @return  string
	 */
	public static function getVimeoId(string $fieldValue): ?string
	{
		if ($fieldValue == '')
		{
			return null;
		}

		// Legacy - allow Vime ID
		if (is_numeric($fieldValue))
		{
			return $fieldValue;
		}

		/*
		 * Parse URL by detecting URL scheme
		 * @see https://developer.vimeo.com/api/oembed/videos#table-1
		 */
		$urlPath = parse_url($fieldValue, PHP_URL_PATH);
		$urlPath = trim($urlPath, '/');

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
				list ($ondemandid, $vimeoId, $unlistedHash) = sscanf($urlPath, 'ondemand/%d/%d/%s');
				break;

			// A regular Vimeo video
			default:
				list ($vimeoId, $unlistedHash) = sscanf($urlPath, '%d/%s');
				break;
		}

		return (string) $vimeoId;
	}

	/**
	 * Get Vimeo parameters that may be used as video src query params
	 *
	 * @param   \Joomla\Registry\Registry  $fieldParams  Field parameters
	 * @return  array
	 *
	 * @see [Vimeo: Using Player Parameters]{@link https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameters
	 */
	public static function getVimeoParams(Registry $fieldParams): array
	{
		// Default Vimeo player parameters
		$defaultVimeoParams = [
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
