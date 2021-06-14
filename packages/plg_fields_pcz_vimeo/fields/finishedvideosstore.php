<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

defined('_JEXEC') or die;

FormHelper::loadFieldClass('List');

/**
 * Form field to load a list of of custom fields for user
 *
 * @since  1.0.0-alpha.2
 */
class JFormFieldFinishedVideosStore extends JFormFieldList
{
	/**
	 * @var string
	 * @inheritdoc
	 */
	protected $type = 'FinishedVideosStore';

	/**
	 * @inheritdoc
	 * @return array
	 */
	public function getOptions(): array
	{
		/** @var \JDatabaseDriver */
		$db = Factory::getDbo();

		// Get the database object and a new query object.
		$query = $db->getQuery(true);

		// Get the database object and a new query object.
		$query
			->select('f.id AS value, f.title AS text')
			->from('#__fields AS f')
			->where('f.context = ' . $db->q('com_users.user'))
			->where('f.type = ' . $db->q('pcz_vimeo_datastore'))
			->where('f.state = ' . $db->q('1'))
			->order('f.title ASC');

		// Respect access controls
		$groups = implode(',', Factory::getUser()->getAuthorisedViewLevels());
		$query->where('f.access IN (' . $groups . ')');

		$db->setQuery($query);

		$options = $db->loadObjectList();

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
