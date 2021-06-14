<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_content_pcz_vimeoprogress
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;

defined('_JEXEC') or die;

JLoader::register('PlgFieldsPcz_VimeoHelper', JPATH_PLUGINS . '/fields/pcz_vimeo/helper.php');

/**
 * Content Vimeo Progress Plugin
 *
 * Note: This plugin may not be needed, as content events are triggered for the fields plugin
 * TODO: Show Watch next episodes button
 *
 * Available Events:
 * - onContentPrepareForm Note: Doesn't fire for com_users.profile|com_users.user, only com_admin.profile
 * - onContentPrepare
 * - onContentAfterTitle Note: disabled when show_intro is off
 * - onContentBeforeDisplay
 * - onContentAfterDisplay
 *
 * Event arguments:
 * View       | $context               | $item class
 * -----------|------------------------|-----------
 * Categories | -                      | -
 * Category   | com_content.categories | CategoryNode
 * Category   | com_content.category   | stdClass
 * Article    | com_content.article:   | stdClass
 *
 * @since  1.0.0-alpha.2
 */
class PlgContentPcz_VimeoProgress extends CMSPlugin
{
	public const TYPE_CATEGORY = 'category';
	public const TYPE_SUBCATEGORY = 'subcategory';

	/**
	 * @var \Joomla\CMS\Application\CMSApplication
	 */
	protected $app = null;

	/**
	 * @var \JDatabaseDriver
	 */
	protected $db = null;

	/**
	 * Alter subcategory description when has child categories
	 * Note: Doesn't trigger on blog layout category and it's children
	 *
	 * Add indicator to article title
	 *
	 * @param   string                                        $context  Context
	 * @param   stdClass|\Joomla\CMS\Categories\CategoryNode  $item     Item
	 * @param   \Joomla\Registry\Registry                     $params   Item params
	 * @param   integer                                       $page     Page number
	 * @return  void
	 */
	public function onContentPrepare($context, &$item, &$params, $page = 0): void
	{
		/*
		 * Funky events such as com_content.category.title
		 * Unfortunately not enough data to be useful
		 */
		if ($params instanceof Registry === false)
		{
			return;
		}

		if (!$params->get('custom_fields_enable'))
		{
			return;
		}

		$allowedCatIds = $this->params->get('filter_cat_ids', []);

		// Category
		if ($item instanceof CategoryNode)
		{
			if (!$this->params->get('enable_subcategories', 1))
			{
				return;
			}

			/** @var \Joomla\CMS\Categories\CategoryNode */
			$categoryNode = $item;

			// Skip when Subcategories Descriptions is set to hide
			if (!$categoryNode->getParams()->get('show_subcat_desc'))
			{
				return;
			}

			// Get data store ID
			$dataStoreId = static::getDataStoreId();

			// Data store not enabled
			if (!$dataStoreId)
			{
				return;
			}

			$seenVimeoIds = $this->getSeenVimeoIds($dataStoreId);

			// Get the path for layout file
			$layoutPath = JPluginHelper::getLayoutPath('content', 'pcz_vimeoprogress', 'progress');

			foreach ($categoryNode->getChildren(true) as $child)
			{
				if (!static::isCategoryAllowed($child, $allowedCatIds))
				{
					continue;
				}

				$vimeoIdsInCategory = $this->getVimeoIdsForCategory($categoryNode, $child);

				$progress = static::getProgress($vimeoIdsInCategory, $seenVimeoIds);

				// No fields assigned for this content
				if (empty($progress))
				{
					continue;
				}

				$output = $this->renderLayout($layoutPath, $progress, static::TYPE_SUBCATEGORY, $child, $context);

				/*
				 * Add child descriptions
				 * as there are no content events for category listings
				 */
				$child->description = $output . $child->description;
			}
		}
		// Article
		elseif (property_exists($item, 'title'))
		{
			if (!$this->params->get('enable_items', 1))
			{
				return;
			}

			// Quick check to see if pcz_vimeo fields are assigned to article
			$fieldTypes = array_column($item->jcfields, 'type');

			if (!in_array('pcz_vimeo', $fieldTypes))
			{
				return;
			}

			// Get data store ID
			$dataStoreId = static::getDataStoreId();

			if (!$dataStoreId)
			{
				return;
			}

			$contentCategories = Categories::getInstance('Content');
			$category = $contentCategories->get($item->catid);

			if (!$category || !static::isCategoryAllowed($category, $allowedCatIds))
			{
				return;
			}

			$seenVimeoIds = $this->getSeenVimeoIds($dataStoreId);

			$progress = [];

			foreach ($item->jcfields as $field)
			{
				if ($field->type !== 'pcz_vimeo' || empty($field->rawvalue))
				{
					continue;
				}

				// Filter by allowed field IDs
				$allowedFieldIds = $this->params->get('filter_field_ids', []);

				if (!empty($allowedFieldIds) && !in_array($field->id, $allowedFieldIds))
				{
					continue;
				}

				$vimeoId = PlgFieldsPcz_VimeoHelper::getVimeoId($field->rawvalue);

				$progress[$vimeoId] = in_array($vimeoId, $seenVimeoIds);
			}

			// No assigned vimeo IDs
			if (empty($progress))
			{
				return;
			}

			$isDone = !empty(array_filter($progress));

			// Note: Don't use fallback values to allow empty strings
			$item->title .= JText::_($isDone
				? $this->params->get('article_seen_indicator_true')
				: $this->params->get('article_seen_indicator_false')
			);
		}

		return;
	}

	/**
	 * Add indicator to category page heading
	 *
	 * @param   string                                        $context - Context
	 * @param   stdClass|\Joomla\CMS\Categories\CategoryNode  $item    - Item
	 * @param   \Joomla\Registry\Registry                     $params  - Item params
	 * @param   integer                                       $page    - Page number
	 * @return  string|null
	 */
	public function onContentBeforeDisplay($context, &$item, &$params, $page = 0): ?string
	{
		if (!$this->params->get('enable_category', 1))
		{
			return null;
		}

		if (!$params->get('custom_fields_enable'))
		{
			return null;
		}

		// Allow only categories
		if ($item instanceof CategoryNode === false)
		{
			return null;
		}

		// Get data store ID
		$dataStoreId = static::getDataStoreId();

		if (!$dataStoreId)
		{
			return null;
		}

		$allowedCatIds = $this->params->get('filter_cat_ids', []);

		if (!static::isCategoryAllowed($item, $allowedCatIds))
		{
			return null;
		}

		$seenVimeoIds = $this->getSeenVimeoIds($dataStoreId);

		$vimeoIdsInCategory = $this->getVimeoIdsForCategory($item);

		$progress = static::getProgress($vimeoIdsInCategory, $seenVimeoIds);

		// No fields assigned for this content
		if (empty($progress))
		{
			return null;
		}

		// Get the path for layout file
		$layoutPath = JPluginHelper::getLayoutPath('content', 'pcz_vimeoprogress', 'progress');

		return $this->renderLayout($layoutPath, $progress, static::TYPE_CATEGORY, $item, $context);
	}

	/**
	 * Get Vimeo IDs for category items using native Joomla models
	 *
	 * @param   \Joomla\CMS\Categories\CategoryNode  $categoryNode  Page category node
	 * @param   \Joomla\CMS\Categories\CategoryNode  $child         Get data Just for this one
	 * @return  integer[]
	 */
	protected function getVimeoIdsForCategory(CategoryNode $categoryNode, CategoryNode $child = null): array
	{
		/** @var array Category to article IDs map (cached per page) */
		static $map = null;

		if ($map === null)
		{
			/** @var \ContentModelArticles */
			$articlesModel = BaseDatabaseModel::getInstance('Articles', 'ContentModel', array('ignore_request' => true));

			/*
			// J!4.1
			// @var \Joomla\Component\Content\Site\Model\ArticlesModel $model
			$articlesModel = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()
				->createModel('Articles', 'Site', ['ignore_request' => true]);
			*/

			/** @var \Joomla\CMS\Applicaton\SiteApplication */
			$app = $this->app;

			// Set application parameters in model
			$articlesModel->setState('params', $app->getParams());

			$articlesModel->setState('list.start', 0);
			$articlesModel->setState('list.limit', null);

			$articlesModel->setState('filter.published', true);
			$articlesModel->setState('load_tags', false);

			// Access filter (Unauthorized content)
			$access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
			$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->id);
			$articlesModel->setState('filter.access', $access);

			/*
			 * Category filter
			 * Note: Using filter.subcategories to include subcategories doesn't respect published flag
			 */
			$catIds = array_column($categoryNode->getChildren(true), 'id');
			$catIds[] = $categoryNode->id;
			$articlesModel->setState('filter.category_id', $catIds);

			// Filter by language
			$articlesModel->setState('filter.language', $app->getLanguageFilter());

			$articles = $articlesModel->getItems();

			// Check access levels
			if (!$access)
			{
				$articles = array_filter(
					$articles,
					function (object $item) use ($authorised): bool {
						return in_array($item->access, $authorised);
					}
				);
			}

			if (empty($articles))
			{
				return [];
			}

			$articleIds = array_map(
				function (object $item): int {
					return (int) $item->id;
				},
				$articles
			);

			// Get field value for articles
			$selectQuery = $this->db->getQuery(true);
			$selectQuery
				// Get all field values
				->select('v.value, v.item_id')
				->from('#__fields_values AS v')
				->where('v.item_id IN (' . implode(', ', $articleIds) . ')')
				->join('LEFT', '#__fields AS f ON v.field_id = f.id')
				->where('f.context = ' . $this->db->q('com_content.article'))
				->where('f.type = ' . $this->db->q('pcz_vimeo'));

			// Filter by allowed field IDs
			$allowedFieldIds = $this->params->get('filter_field_ids', []);

			if (!empty($allowedFieldIds))
			{
				$selectQuery->where('f.id IN (' . implode(', ', $allowedFieldIds) . ')');
			}

			$this->db->setQuery($selectQuery);

			$itemToValue = $this->db->loadAssocList('item_id', 'value');

			// Group articles by category
			$map = array_fill_keys($catIds, []);

			// Map to map, skip articles without assigned video id
			foreach ($articles as $item)
			{
				if (array_key_exists($item->id, $itemToValue)
					&& array_key_exists($item->catid, $map)
				)
				{
					$map[$item->catid][] = PlgFieldsPcz_VimeoHelper::getVimeoId($itemToValue[$item->id]);
				}
			}
		}

		// Resolve category to get data for
		$mainCategory = $child ?: $categoryNode;

		// Get values for root
		$vimeoIdsForCategory = $map[$mainCategory->id];

		// Get values for children
		foreach ($mainCategory->getChildren(true) as $child)
		{
			$vimeoIdsForCategory = array_merge($vimeoIdsForCategory, $map[$child->id]);
		}

		return $vimeoIdsForCategory;
	}

	/**
	 * Get finished videos for user
	 *
	 * @param   integer  $dataStoreId  Data store ID
	 * @return  integer[]
	 */
	protected function getSeenVimeoIds(int $dataStoreId): array
	{
		// Cached data
		static $seenVimeoIds = null;

		if ($seenVimeoIds === null)
		{
			$user = Factory::getUser();

			// Get all seen
			$selectQuery = $this->db->getQuery(true);

			$selectQuery
				->select('value')
				->from('#__fields_values')
				->where('field_id = ' . $this->db->q($dataStoreId))
				->where('item_id = ' . $this->db->q($user->id));

			$this->db->setQuery($selectQuery);

			$seenVimeoIds = $this->db->loadColumn();
			$seenVimeoIds = array_map('intval', $seenVimeoIds);
		}

		return $seenVimeoIds;
	}

	/**
	 * Get data store id
	 *
	 * @return integer|null
	 */
	protected static function getDataStoreId(): ?int
	{
		/** @var object */
		$vimeoPlugin = PluginHelper::getPlugin('fields', 'pcz_vimeo');

		// Plugin not enabled
		if (empty($vimeoPlugin))
		{
			return null;
		}

		$vimeoPluginParams = new Registry($vimeoPlugin->params);
		$dataStoreId = $vimeoPluginParams->get('data_store');

		// Data store disabled
		if (!$dataStoreId)
		{
			return null;
		}

		return (int) $dataStoreId;
	}

	/**
	 * Check if category or any of it's parents is allowed
	 *
	 * @param   CategoryNode  $categoryNode   Category node
	 * @param   string[]      $allowedCatIds  Allowed category IDs
	 * @return  boolean
	 */
	protected static function isCategoryAllowed(CategoryNode $categoryNode, array $allowedCatIds): bool
	{
		// ALlow when no filters is set
		if (empty($allowedCatIds))
		{
			return true;
		}

		do
		{
			if (in_array($categoryNode->id, $allowedCatIds))
			{
				return true;
			}
		}
		while ($categoryNode = $categoryNode->getParent());

		return false;
	}

	/**
	 * Get progress table in format [id] => bool
	 *
	 * @param   array  $vimeoIds      Known video IDs
	 * @param   array  $seenVimeoIds  Seen video IDs
	 * @return  boolean[]
	 */
	protected static function getProgress(array $vimeoIds, array $seenVimeoIds): array
	{
		$progress = [];

		foreach ($vimeoIds as $vimeoId)
		{
			$progress[$vimeoId] = in_array($vimeoId, $seenVimeoIds);
		}

		return $progress;
	}

	/**
	 * Render plugin layout
	 *
	 * @param   string                 $path      Layout path
	 * @param   boolean[]              $progress  Progress table
	 * @param   string                 $type      One of 'category'|'subcategory'|'item'
	 * @param   stdClass|CategoryNode  $item      Item
	 * @param   string                 $context   Context
	 * @return  string
	 */
	protected function renderLayout(string $path, array $progress, string $type, $item, $context): string
	{
		ob_start();
		include $path;

		return ob_get_clean();
	}
}
