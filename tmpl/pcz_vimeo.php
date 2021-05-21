<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @see [Vimeo: Embed options]{@link https://developer.vimeo.com/player/sdk/embed}
 * @see [Vimeo: oEmbed]{@link https://developer.vimeo.com/api/oembed}
 */

defined('_JEXEC') or die;

/**
 * Layout variables (See Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin::onCustomFieldsPrepareField)
 * ----------------
 * @var  string $context                         Component context (com_content.article)
 * @var  object $item                            Subject (article)
 * @var  object $field                           Field info
 * @var  \Joomla\Registry\Registry $fieldParams  Field parameters
 * @var  string $path                            Path to this layout file
 */

$value = $field->value;

if ($value == '')
{
	return;
}

// Create UID (not used atm)
$elementUid = sprintf('plg_fields_%s:%s:%d', $field->type, $context, $field->id);
?>
<div
	class="pcz_vimeo-video pcz_vimeo-video--aspect-ratio-<?= $fieldParams->get('aspect_ratio', '16-9') ?>"
	data-vimeo-id="<?= htmlspecialchars($value) ?>"
>
	<iframe
		id="<?= $elementUid ?>"
		class="pcz_vimeo-video__element"
		src="https://player.vimeo.com/video/<?= htmlspecialchars($value) ?>?<?= http_build_query(PlgFieldsPcz_Vimeo::getVimeoParams($fieldParams)) ?>"
		frameborder="0"
		allow="autoplay; fullscreen; picture-in-picture"
		allowfullscreen
		loading="lazy"
	></iframe>
</div>
