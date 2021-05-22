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

use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;

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

// Note: Assets are added to document during content events, even when layout is not displayed

// Joomla 4.x
if ((new Version)->isCompatible('4.0'))
{
	$wa = $this->app->getDocument()->getWebAssetManager();
	$wa->getRegistry()->addExtensionRegistryFile('plg_fields_pcz_vimeo');

	$wa
		->useScript('keepalive')
		->useStyle('plg_fields_pcz_vimeo.templates');
}
// Joomla 3.x
else
{
	HTMLHelper::_('behavior.keepalive');
	HTMLHelper::_('stylesheet', 'plg_fields_pcz_vimeo/pcz_vimeo.css', ['version' => '1.0.0-alpha.1', 'relative' => true]);
}

$vimeoParams = PlgFieldsPcz_Vimeo::getVimeoParams($fieldParams);
?>
<div class="pcz_vimeo-video pcz_vimeo-video--aspect-ratio-<?php echo $fieldParams->get('aspect_ratio', '16-9') ?>">
	<iframe
		class="pcz_vimeo-video__element"
		src="https://player.vimeo.com/video/<?php echo htmlspecialchars($value) ?>?<?php echo http_build_query($vimeoParams) ?>"
		frameborder="0"
		allow="autoplay; fullscreen; picture-in-picture"
		allowfullscreen
		loading="lazy"
	></iframe>
</div>
