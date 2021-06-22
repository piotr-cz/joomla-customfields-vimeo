#!/usr/bin/env php
<?php
/**
 * @package     PczVimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Command line tool to update  media files hashes in web asset manifest file and layout files
 * @see https://github.com/joomla/joomla-cms/blob/4.0.0-rc2/build/build-modules-js/versioning.es6.js
 */

$cwd = getcwd();

$webAssetsGlob = sprintf('%s/packages/*/media/joomla.asset.json', $cwd);

foreach (new GlobIterator($webAssetsGlob) as $fileInfo)
{
	$webAssetsOriginalString = file_get_contents($fileInfo->getPathname());
	$webAssets = json_decode($webAssetsOriginalString, true);

	/** @var array<string, string> - Hash replacements */
	$replacements = [];

	foreach ($webAssets['assets'] as &$webAsset)
	{
		// Skip when scheme is present
		if (parse_url($webAsset['uri'], PHP_URL_SCHEME))
		{
			continue;
		}

		$extension = pathinfo($webAsset['uri'], PATHINFO_EXTENSION);

		// Validate extension
		if (!in_array($extension, ['css', 'js']))
		{
			continue;
		}

		// Resolve full path to web asset
		$pathFragments = explode('/', $webAsset['uri'], 2);

		array_splice($pathFragments, 1, 0, ['media', $extension]);

		$relativePath = implode('/', $pathFragments);

		$absolutePath = sprintf('%s/packages/%s', $cwd, $relativePath);

		if (!is_file($absolutePath))
		{
			continue;
		}

		// Get hash
		$version = sha1_file($absolutePath);

		if ($webAsset['version'] !== $version)
		{
			$webAsset['version'] = $replacements[$webAsset['version']] = $version;
		}
	}

	// Nothing to do
	if (empty($replacements))
	{
		continue;
	}

	// Prepare web assets manifest
	$webAssetsUpdatedString = json_encode($webAssets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

	// Use 2 spaces instead of 4
	$webAssetsUpdatedString = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $webAssetsUpdatedString);

	// Add newline at the end
	$webAssetsUpdatedString = $webAssetsUpdatedString . "\n";

	if ($webAssetsOriginalString === $webAssetsUpdatedString)
	{
		continue;
	}

	// Update
	file_put_contents($fileInfo->getPathname(), $webAssetsUpdatedString);

	/*
	 * Note: Code below replaces hashes in layoyut files using str_replace and so is not reliable.
	 * One should manually update layout files or use (deprected) MD5SUM file or backport web assets functionality
	 */

	// Update layout file
	$layoutsGlob = sprintf('%s/packages/*/tmpl/*', $cwd);

	foreach (new GlobIterator($layoutsGlob) as $fileInfo)
	{
		$fileObject = $fileInfo->openFile('r+');

		/** @var string[] - Updated file lines */
		$fileLines = [];

		/** @var boolean - Updated flag */
		$isContentsChanged = false;

		while (!$fileObject->eof())
		{
			$fileLines[] = str_replace(
				array_keys($replacements),
				array_values($replacements),
				$fileObject->fgets(),
				$count
			);

			if ($count && !$isContentsChanged)
			{
				$isContentsChanged = true;
			}
		}

		if ($isContentsChanged)
		{
			$contents = implode($fileLines);

			$fileObject->rewind();
			$fileObject->fwrite($contents);
		}

		// Close handle
		$fileObject = null;
	}
}

echo 'Done.' . PHP_EOL;
