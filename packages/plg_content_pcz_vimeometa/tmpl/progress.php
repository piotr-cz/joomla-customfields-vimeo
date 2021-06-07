<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeometa
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 * -----------------
 * @var   PlgContentPcz_VimeoMeta  $this
 * @var   bool[]                   $progress
 * @var   string                   $type
 * @var   string                   $context
 * @var   stdClass|\Joomla\CMS\Categories\CategoryNode  $item
 */

$allCount = count($progress);
$finishedCount = count(array_filter($progress));

?>
<div>
	<progress
		max="<?php echo $allCount ?>"
		value="<?php echo $finishedCount ?>"
		title="<?php echo sprintf('%d/ %d', $finishedCount, $allCount) ?>"
	>
		<?php echo ($allCount ? $finishedCount / $allCount : 0) * 100 ?>%
	</progress>
</div>
