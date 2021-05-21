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
use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

// Joomla 3.x & 4.0
JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);

/**
 * Fields Vimeo Plugin
 * Available Site Events:
 * + oncustomfieldspreparefield
 * + oncontentaftertitle
 * + oncontentbeforedisplay
 * + oncontentafterdisplay
 * + onafterdispatch
 * + onbeforecompilehead
 * + onafterrender
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
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		if (!$fieldNode)
		{
			return $fieldNode;
		}

		/*
		 * Set field type and filter
		 * @see https://docs.joomla.org/Special:MyLanguage/J3.x:Adding_custom_fields/Parameters_for_all_Custom_Fields
		 * @see administrator/components/com_fields/src/Plugin/FieldsPlugin.php
		 */
		$fieldNode->setAttribute('type', 'number');
		$fieldNode->setAttribute('filter', 'integer');
		$fieldNode->setAttribute('min', 1);
		$fieldNode->setAttribute('step', 1);

		// Set field options if not filled in admin

		// Label
		if (!$fieldNode->getAttribute('label'))
		{
			$fieldNode->setAttribute('label', 'PLG_FIELDS_PCZ_VIMEO_VALUE_VIDEO_ID_LABEL');
		}

		// Placeholder
		if (!$fieldNode->getAttribute('hint'))
		{
			$fieldNode->setAttribute('hint', '000000000');
		}

		if (!$fieldNode->getAttribute('description'))
		{
			$fieldNode->setAttribute('description', 'PLG_FIELDS_PCZ_VIMEO_VALUE_VIDEO_ID_DESC');
		}

		return $fieldNode;
	}

	/**
	 * @inheritdoc
	 *
	 * @param   string    $context  The context.
	 * @param   stdclass  $item     The item.
	 * @param   stdclass  $field    The field.
	 * @return  string
	 */
	public function onCustomFieldsPrepareField($context, $item, $field)
	{
		// Skip when not inside article (example values: 'com_content.category'|'com_content.article')
		$pageContext = vsprintf(
			'%s.%s',
			[
				$this->app->input->get('option'),
				$this->app->input->get('view'),
			]
		);

		if (in_array($pageContext, ['com_content.category', 'com_user.category']))
		{
			return '';
		}

		return parent::onCustomFieldsPrepareField($context, $item, $field);
	}

	/**
	 * @inheritdoc
	 *
	 * @return  void
	 */
	public function onBeforeCompileHead(): void
	{
		if (!$this->app->isClient('site'))
		{
			return;
		}

		// Get the document object.
		$document = $this->app->getDocument();

		if ($document->getType() !== 'html')
		{
			return;
		}

		// Skip when not inside article
		$pageContext = vsprintf(
			'%s.%s',
			[
				$this->app->input->get('option'),
				$this->app->input->get('view'),
			]
		);

		if (in_array($pageContext, ['com_content.category', 'com_user.category']))
		{
			return;
		}

		// Joomla 3.x
		if (!(new Version)->isCompatible('4.0'))
		{
			HTMLHelper::_('behavior.keepalive');
			HTMLHelper::_('stylesheet', 'plg_fields_pcz_vimeo/pcz_vimeo.css', array('version' => '1.0.0-alpha.1', 'relative' => true));

			// HTMLHelper::_('script', 'plg_fields_pcz_vimeo/pcz_vimeo.js', array('version' => '1.0.0-alpha.1', 'relative' => true));
			// HTMLHelper::_('script', 'plg_fields_pcz_vimeo/player.min.js', array('version' => '2.15.0', 'relative' => true));
			// $document->addScriptDeclaration('window.addEventListener("load", function() { })');

			return;
		}

		// Joomla 4.x
		$wa = $document->getWebAssetManager();

		/*
		 * Add extension registry file In media dir
		 * Note: Version may be set only on manual WebAssetItem init
		 */
		$wa->getRegistry()->addExtensionRegistryFile('plg_fields_pcz_vimeo');

		$wa
			->useScript('keepalive')
			->useStyle('plg_fields_pcz_vimeo.templates')

			// ->useScript('plg_fields_pcz_vimeo.templates')
			// ->useScript('@vimeo/player')
			/*
			// Note: Element uid constructed from $field->type, $context, $field->id
			->addInlineScript('window.addEventListener("load", function() { }',
				['name' => 'inline.plg.fields.pcz_vimeo'],
				['type' => 'module'],
				['@vimeo/player']
			)
			// */
		;
	}

	/**
	 * Get Vimeo parameters that may be used as video src query params
	 *
	 * @param   \Joomla\Registry\Registry  $fieldParams  Field parameters
	 * @return  array
	 *
	 * @see [Vimeo: Using Player Parameters]{@link https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameters
	 */
	public static function getVimeoParams(Registry $fieldParams)
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
			// 'player_id'   => 0, // ??
			// 'app_id'      => 0, // ??
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
