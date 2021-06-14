<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

defined('_JEXEC') or die;

if (!$field->value)
{
	return;
}
?>
<ul>
	<?php foreach ((array) $field->value as $value) : ?>
		<li><?php echo $value ?></li>
	<?php endforeach ?>
</ul>
