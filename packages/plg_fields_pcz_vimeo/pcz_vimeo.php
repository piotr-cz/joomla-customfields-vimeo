<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Joomla 4.x
 * use Joomla\CMS\Form\Form;
 * use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;
 */

use Joomla\CMS\Version;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

// Joomla 3.x & 4.0
JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
JLoader::import('components.com_fields.libraries.fieldslistplugin', JPATH_ADMINISTRATOR);

JLoader::register('PlgFieldsPcz_VimeoHelper', __DIR__ . '/helper.php');

/**
 * Fields Vimeo Plugin
 *
 * Available Site Events:
 * - onCustomFieldsBeforePrepareField
 * - onCustomFieldsPrepareField
 * - onCustomFieldsAfterPrepareField
 * - onAfterDispatch
 * - onBeforeCompilehead
 * - onAfterRender
 * Available admin events:
 * - onCustomFieldsPrepareDom
 * - onContentPrepareForm
 *
 * @since  1.0.0-alpha.1
 */
class PlgFieldsPcz_Vimeo extends FieldsListPlugin
{
	protected const TYPE_DEFAULT = 'pcz_vimeo';
	protected const TYPE_DATASTORE = 'pcz_vimeo_datastore';

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
	 * @var \JDatabaseDriver
	 */
	protected $db;

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

		// Switch to seen videos list on user profile pages
		if ($field->type === static::TYPE_DATASTORE)
		{
			// It's a list of predefined values so don't have to validate or sanitize
			$fieldNode->setAttribute('type', 'list');
			$fieldNode->setAttribute('multiple', 'true');

			// J!4
			if ((new Version)->isCompatible('4.0'))
			{
				$fieldNode->setAttribute('layout', 'joomla.form.field.list-fancy-select');
			}

			return $fieldNode;
		}

		/*
		 * Set field type, filter and validation rule
		 * @see https://docs.joomla.org/Special:MyLanguage/J3.x:Adding_custom_fields/Parameters_for_all_Custom_Fields
		 * @see administrator/components/com_fields/src/Plugin/FieldsPlugin.php
		 */

		// Require helper for filter functions called by JForm validate.
		JLoader::register('JFormRuleVimeoVideoLink', __DIR__ . '/rules/vimeovideolink.php');

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
	 * Get available values for data store
	 * TODO: Evaluate linking date store to default field (field -> data store relationship)
	 *       Possoble problem with db query as it's set fieldparams which is a JSON object
	 *
	 * @param   stdClass  $field - Data store field
	 * @return  string[]
	 */
	public function getOptionsFromField($field): array
	{
		$query = $this->db->getQuery(true);

		$query
			->select('v.value')
			->from('#__fields_values AS v')
			->join('LEFT', '#__fields AS f ON v.field_id = f.id')
			->where('f.id <> ' . $this->db->q($field->id))
			->where('f.type = ' . $this->db->q(static::TYPE_DEFAULT));

		$this->db->setQuery($query);

		$values = $this->db->loadColumn();

		$keys = array_map('PlgFieldsPcz_VimeoHelper::getVimeoId', $values);

		return array_combine($keys, $values);
	}

	/**
	 * @inheritdoc
	 * Cannot use isTypeSupported to omit unsupported type from list
	 * as fields component helper is using directly onCustomFieldsGetTypes event
	 *
	 * @return string[][]
	 */
	public function onCustomFieldsGetTypes(): array
	{
		$fieldTypes = parent::onCustomFieldsGetTypes();

		$component = $this->app->input->get('option');
		$fieldsContext = $this->app->input->get('context');

		// Keep all types for users component or fields component with user context
		if ($component === 'com_users'
			|| ($component === 'com_fields' && $fieldsContext === 'com_users.user')
		)
		{
			return $fieldTypes;
		}

		// Remove datastore on others contexts
		return array_filter(
			$fieldTypes,
			function (array $fieldType): bool {
				return $fieldType['type'] !== static::TYPE_DATASTORE;
			}
		);
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

		// Render as list on user profile, as it's most probably a data store (extra call to getOptionsFromField)
		if ($context === 'com_users.user')
		{
			$path = JPluginHelper::getLayoutPath('fields', 'list', 'list');

			ob_start();
			include $path;

			return ob_get_clean();
		}

		return parent::onCustomFieldsPrepareField($context, $item, $field);
	}

	/**
	 * Ajax handler
	 * Note: Apparently there is a dedicated 'ajax' plugin group
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function onAjaxPcz_Vimeo() // phpcs:ignore Joomla.NamingConventions.ValidFunctionName
	{
		/*
		 * Check X-CSRF-Token appended by Joomla.request
		 * https://docs.joomla.org/How_to_add_CSRF_anti-spoofing_to_forms
		 * Not using `JSession::checkToken();` which adds system message on fail
		 */

		/*
		$token = \Joomla\CMS\Session\Session::getFormToken();

		if (!$token !== $this->app->input->server->get('HTTP_X_CSRF_TOKEN', null, 'alnum')) {
			throw new Exception('JLIB_ENVIRONMENT_SESSION_EXPIRED');
		}
		*/

		// Get user and check session session
		$user = Factory::getUser();

		if ($user->guest || $user->block)
		{
			throw new \LogicException('Unauthorized');
		}

		// Note: Skipping user permission check (Edit Custom Field Value) and assume creating is allowed for all authenticated users

		$dataStoreId = $this->params->get('data_store');

		if (!$dataStoreId)
		{
			return null;
		}

		/** @var int|null */
		$vimeoId = $this->app->input->get('vimeoId', null, 'int');

		// Check if relation is already set
		$selectQuery = $this->db->getQuery(true);

		$selectQuery
			->select('*')
			->from('#__fields_values')
			->where('field_id = ' . $this->db->q($dataStoreId))
			->where('item_id = ' . $this->db->q($user->id))
			->where('value = ' . $this->db->q($vimeoId));

		$this->db->setQuery($selectQuery);

		$result = $this->db->loadAssoc();

		if ($result)
		{
			return 'OK';
		}

		// Store relation (field:user:vimeoId in custom field)
		$insertQuery = $this->db->getQuery(true);

		$insertQuery
			->insert($this->db->qn('#__fields_values'))
			->columns(
				[
					$this->db->qn('field_id'),
					$this->db->qn('item_id'),
					$this->db->qn('value'),
				]
			)
			// phpcs:disable PEAR.Functions.FunctionCallSignature
			->values(implode(',', [
					$this->db->q($dataStoreId),
					$this->db->q($user->id),
					$this->db->q($vimeoId),
				])
			);
			// phpcs:enable PEAR.Functions.Generic.FunctionCallSignature

		$this->db->setQuery($insertQuery);

		if (!$this->db->execute())
		{
			throw new \RuntimeException('Failure');
		}

		return 'OK';
	}
}
