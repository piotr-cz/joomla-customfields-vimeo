<?php
/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @see [Vimeo: Embed options]{@link https://developer.vimeo.com/player/sdk/embed}
 * @see [Vimeo: oEmbed]{@link https://developer.vimeo.com/api/oembed}
 */

use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Uri\Uri;

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

$vimeoId = PlgFieldsPcz_VimeoHelper::getVimeoId($field->value);
$vimeoHash = PlgFieldsPcz_VimeoHelper::getVimeoHash($field->value);

if (!$vimeoId)
{
	return;
}

$document = $this->app->getDocument();

$vimeoParams = PlgFieldsPcz_VimeoHelper::getVimeoParams($fieldParams);

$vimeoSrc = new URI(sprintf('https://player.vimeo.com/video/%s', $vimeoId));
$vimeoSrc->setQuery($vimeoParams);
// Hash is required on embeded player since mid-2021, see https://vimeo.zendesk.com/hc/en-us/articles/4409305565069-Embedded-player-displays-This-video-does-not-exist-message
$vimeoSrc->setVar('h', $vimeoHash);

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

	$document->addScript('https://player.vimeo.com/api/player.js', ['version' => null], ['defer' => true]);

	HTMLHelper::_('stylesheet', 'plg_fields_pcz_vimeo/pcz_vimeo.css', ['version' => 'b5db833cd5e9910865adfa7ecd624754b6608293', 'relative' => true]);
	HTMLHelper::_('script', 'plg_fields_pcz_vimeo/pcz_vimeo.es6.js', ['version' => '1ebe3f01635603e583d327a993f43180382b5801', 'relative' => true], ['defer' => true]);
}

// J!4 & J!3
$document->addScriptOptions(
	'plg_fields_pcz_vimeo',
	[
		'uri' => Route::_('index.php?option=com_ajax'),
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
		src="<?php echo $vimeoSrc ?>"
		frameborder="0"
		allow="autoplay; fullscreen; picture-in-picture"
		allowfullscreen
		loading="lazy"
	></iframe>
</div>
