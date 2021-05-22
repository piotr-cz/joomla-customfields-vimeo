# Joomla! Vimeo Custom Field Plugin

This plugin lets you create new fields of type 'Vimeo' in any extensions where custom fields are supported.


## Requirements

- Joomla 3.7+
- PHP 7.1+


## Installation

1. Install:
   Download [latest release](https://github.com/piotr-cz/joomla-customfields-vimeo/releases/latest) and install using Extension Manager (_Extensions > Manage > Install > Upload Package File_)

1. Enable Plugin
   (_Extensions > Plugins > Fields - Pcz - Vimeo > Enable_)


## Configuration

Custom field may be configured

- globally in plugin (Extensions > Plugins > Fields - Pcz - Vimeo)

- per field


### Available settings

- Aspect ratio

- All Vimeo Player Parameters (See _Vimeo Help Center > [Using Player Parameters](https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameter)_)

  Notes:

  - Some parameters have effect only if video owner allows (_Byline_, _Portrait_, _Title_)

    See _Vimeo admin panel > Videos > [video] > Advanced > Embed > Your details > Let users decide_

  - Some parameters are available only on Vimeo Plus account or higher (_Background_, _Color_, _Controls_, _Quality_, _Speed_).
