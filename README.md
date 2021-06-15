# Joomla! Vimeo Custom Field Package

This plugins lets you create new fields of type 'Vimeo' in any extensions where custom fields are supported.

Package contains following extensions:

- **[Vimeo Fields plugin](#vimeo-fields-plugin)**

  This plugin adds ability to add Vimeo to an article (or any other supported content) by defining Vimeo URL.

- **[Vimeo Progress Content plugin](#vimeo-progress-content-plugin)**

  This plugin adds ability to indicate seen Vimeo videos for logged-in users.  
  It does it by adding
  
  - an indicator to an article title (✓)
  - progress bar after category and subcategory title (▓▓░░░)


## Requirements

- Joomla 3.7+ or 4.0-beta.7+

- PHP 7.1+


## Installation

1. Install

   - by pasting URL to zip file of [latest release](https://github.com/piotr-cz/joomla-customfields-vimeo/releases/latest)  
     *Extensions > Manage > Install > Install from URL*

   - by downloading [latest release](https://github.com/piotr-cz/joomla-customfields-vimeo/releases/latest) and installing it using Extension Manager  
     *Extensions > Manage > Install > Upload Package File*

1. Enable Plugins  
   *Extensions > Plugins > Fields - Pcz - Vimeo > Enable*  
   *Extensions > Plugins > Content - Pcz - Vimeo Progress > Enable* (Optional)


## Vimeo Fields plugin

### Configuration

Custom field may be configured either

- globally in plugin  
  *Extensions > Plugins > Fields - Pcz - Vimeo*

- per each field


#### Available settings

- Aspect ratio

- Disable on category views

- Finished videos data store

- All Vimeo Player Parameters (See _Vimeo Help Center > [Using Player Parameters](https://vimeo.zendesk.com/hc/en-us/articles/360001494447-Using-Player-Parameter)_)

  Notes:

  - Some parameters are available only on Vimeo Plus account or higher (_Background_, _Color_, _Controls_, _Quality_, _Speed_).

  - Some parameters have effect only if video owner allows (_Byline_, _Portrait_, _Title_).

    See _Vimeo admin panel > Videos > [video] > Advanced > Embed > Your details > Let users decide_


## Vimeo progress Content plugin

### Configuration

First of all, you have create storage where data about videos seen by users will be saved to.
Plugin doesn't create it's own storage (like database table) but uses custom fields within user context.

To add finished Videos data store, add new field with type *Vimeo Datastore*

1. *Users > Fields > New*

1. Title: *Videos seen by users* (or any custom name)

1. Type: *Vimeo Datastore*

1. Optional: If you want to hide data from users, you may restrict *Access* level, set *Options > Editable in: Site* or set *Options > Display when Read-Only: No*

Now set up Vimeo Fields plugin to use the storage you just created:

1. *Extensions > Plugins > Fields - Pcz - Vimeo*

1. Finished videos data store: *Videos seen by users* (or custom name you entered)


### Available settings

- Enable for category

- Enable for subcategories

- Enable for articles

  - Article seen idicator

  - Article unseen indicator

- Categories to process

- Fields to process


## Testing pre-releases

To allow updates for latest commit switch  
*Global Configuration > Installer > Minimum Extension Stability* to *Development*.

Available extension update will show up as version *99.0.0-alpha* and install as of latest commit from main branch of this repository.


## Known issues

### Joomla 4

- Radio controls in plugin settings look different to Joomla 4 native ones.

  This is a aide effect of having extension compatible with both Jooma 3 and Joomla 4.
