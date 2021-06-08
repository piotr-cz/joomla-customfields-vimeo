# Joomla! Vimeo Custom Field Plugin

This plugin lets you create new fields of type 'Vimeo' in any extensions where custom fields are supported.


## Requirements

- Joomla 3.7+ or 4.0-beta.7+
- PHP 7.1+


## Installation

1. Install

   - by pasting URL to zip file of [latest release](https://github.com/piotr-cz/joomla-customfields-vimeo/releases/latest)  
     *Extensions > Manage > Install > Install from URL*

   - download [latest release](https://github.com/piotr-cz/joomla-customfields-vimeo/releases/latest) and install using Extension Manager  
     *Extensions > Manage > Install > Upload Package File*

1. Enable Plugin  
   *Extensions > Plugins > Fields - Pcz - Vimeo > Enable*


## Configuration

Custom field may be configured

- globally in plugin  
  *Extensions > Plugins > Fields - Pcz - Vimeo*

- per each field


### Available settings

- Aspect ratio

- Disable on category views

- Finished videos data store

- All Vimeo Player Parameters (See _Vimeo Help Center > [Using Player Parameters](https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameter)_)

  Notes:

  - Some parameters are available only on Vimeo Plus account or higher (_Background_, _Color_, _Controls_, _Quality_, _Speed_).

  - Some parameters have effect only if video owner allows (_Byline_, _Portrait_, _Title_).

    See _Vimeo admin panel > Videos > [video] > Advanced > Embed > Your details > Let users decide_


# Known issues

## Joomla 4

- Radio controls in plugin settings look different to Joomla 4 native ones.

  This is a aide effect of having extension compatible with both J!3 & J!4.
