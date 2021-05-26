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

		// Require helper for filter functions called by JForm validate.
		JLoader::register('JFormRuleVimeoVideoLink', __DIR__ . '/rules/vimeovideolink.php');

		/*
		 * Set field type, filter and validation rule
		 * @see https://docs.joomla.org/Special:MyLanguage/J3.x:Adding_custom_fields/Parameters_for_all_Custom_Fields
		 * @see administrator/components/com_fields/src/Plugin/FieldsPlugin.php
		 */
		$fieldNode->setAttribute('type', 'url');
		$fieldNode->setAttribute('filter', 'JFormRuleVimeoVideoLink::filterVimeoVideoLink');
		$fieldNode->setAttribute('validate', 'VimeoVideoLink');

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
	 * @inheritdoc
	 *
	 * @param   string    $context  The context.
	 * @param   stdClass  $item     The item.
	 * @param   stdClass  $field    The field.
	 * @return  string|void
	 */
	public function onCustomFieldsPrepareField($context, $item, $field): ?string
	{
		// Check if the field should be processed by us
		if (!$this->isTypeSupported($field->type))
		{
			return null;
		}

		// Merge the params from the plugin and field which has precedence
		$fieldParams = clone $this->params;
		$fieldParams->merge($field->fieldparams);

		$componentView = $this->app->input->get('view');

		// Skip on blog/ list layouts
		if ($fieldParams->get('disable_on_category', 1) && $componentView === 'category')
		{
			return null;
		}

		return parent::onCustomFieldsPrepareField($context, $item, $field);
	}
}
