<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

defined('_JEXEC') or die;

$fieldValue = $field->value;

if (empty($fieldValue))
{
	return;
}

$texts      = [];
$options    = $this->getOptionsFromField($field);
?>
<ul>
	<?php foreach ($options as $value => $name) : ?>
		<?php if (in_array((string) $value, $fieldValue)) : ?>
			<li><?php echo $name ?></li>
		<?php endif ?>
	<?php endforeach ?>
</ul>
