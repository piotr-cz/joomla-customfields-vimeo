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

// Require helper
JLoader::register('PlgFieldsPcz_VimeoHelper', __DIR__ . '/../helper.php');

/**
 * Layout variables (See Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin::onCustomFieldsPrepareField)
 * ----------------
 * @var  PlgFieldsPcz_Vimeo $this
 * @var  string $context                         Component context (com_content.article)
 * @var  object $item                            Subject (article)
 * @var  object $field                           Field info
 * @var  \Joomla\Registry\Registry $fieldParams  Field parameters
 * @var  string $path                            Path to this layout file
 */

/** @var string|null */
$vimeoId = PlgFieldsPcz_VimeoHelper::getVimeoId($field->value);

if (!$vimeoId)
{
	return;
}

$document = $this->app->getDocument();

$vimeoParams = PlgFieldsPcz_VimeoHelper::getVimeoParams($fieldParams);

// Note: Assets are added to document during content events, even when layout is not displayed

// Joomla 4.x
if ((new Version)->isCompatible('4.0'))
{
	$wa = $document->getWebAssetManager();
	$wa->getRegistry()->addExtensionRegistryFile('plg_fields_pcz_vimeo');

	$wa
		->useScript('core')
		->useScript('keepalive')
		->useStyle('plg_fields_pcz_vimeo.templates')
		->useScript('plg_fields_pcz_vimeo.templates');
}
// Joomla 3.x
else
{
	// Load core js stuff
	HTMLHelper::_('behavior.core');
	HTMLHelper::_('behavior.keepalive');
	HTMLHelper::_('stylesheet', 'plg_fields_pcz_vimeo/pcz_vimeo.css', ['version' => '1.0.0-alpha.1', 'relative' => true]);
	HTMLHelper::_('script', 'plg_fields_pcz_vimeo/pcz_vimeo.es6.js', ['version' => '1.0.0-alpha.1', 'relative' => true]);
}

// J!4 & J!3
$document->addScript('https://player.vimeo.com/api/player.js');
$document->addScriptOptions(
	'plg_fields_pcz_vimeo',
	[
		'uri' => JRoute::_('index.php?option=com_ajax'),
	]
);

$elementParams = [
	'vimeoId' => $vimeoId,
	'logEnded' => (bool) $this->params->get('data_store'),
];
?>
<div class="pcz_vimeo-video pcz_vimeo-video--aspect-ratio-<?php echo str_replace(':', '-', $fieldParams->get('aspect_ratio', '16:9')) ?>">
	<iframe
		data-plg_fields_pcz_vimeo='<?php echo json_encode($elementParams) ?>'
		class="pcz_vimeo-video__element"
		src="https://player.vimeo.com/video/<?php echo $vimeoId ?>?<?php echo http_build_query($vimeoParams) ?>"
		frameborder="0"
		allow="autoplay; fullscreen; picture-in-picture"
		allowfullscreen
		loading="lazy"
	></iframe>
</div>
