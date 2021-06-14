// @ts-check

/**
 * @package     PczVimeo
 * @subpackage  plg_fields_pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @typedef { import('@vimeo/player').Player } Player
 */

(function(document, Joomla, Vimeo) {
  'use strict';

  /**
   * Fire Joomla request to ajax component
   * @param {string} vimeoId
   * @param {string} url
   * @param {function} onSuccess
   * @return
   */
  const request = function(url, vimeoId, onSuccess) {
    Joomla.request({
      url,
      method: 'POST',
      data: new URLSearchParams({
        format: 'json',
        plugin: 'Pcz_Vimeo',
        group: 'fields',
        ignoreMessages: '1',
        vimeoId: vimeoId,
      }),
      onSuccess: function(/** @type {string} */responseText) {
        /** @type {{success: boolean, message: string|null, messages: null, data: any[]}} */
        const response = JSON.parse(responseText)

        if (response.success)
          onSuccess(response.data)
        }
      })
    }

  /**
   * Initialize
   */
  const onBoot = function() {
    // Check dependencies
    if (!Joomla || !Vimeo) {
      throw new Error('core.js was not properly initialised');
    }

    /** @type {{uri: string}} */
    const options = Joomla.getOptions('plg_fields_pcz_vimeo')

    // Check configured options
    if (!options) {
      return
    }

    // Create player instances and attach ended events
    /** @type {HTMLIFrameElement[]} */
    const iframes = [].slice.call(document.querySelectorAll('[data-plg_fields_pcz_vimeo]'))

    iframes.forEach(function (iframe) {
      /** @type {{vimeoId: string, logEnded: boolean}} */
      const params = JSON.parse(iframe.dataset.plg_fields_pcz_vimeo)
      /** @type {Player} */
      const player = new Vimeo.Player(iframe)

      if (params.logEnded) {
        const handleVideoEnded = function() {
          request(options.uri, params.vimeoId, function() {
            player.off('ended', handleVideoEnded)
          })
        }

        player.on('ended', handleVideoEnded)
      }
    })
  }

  // Boot after all deferred scripts are executed
  document.addEventListener('DOMContentLoaded', onBoot, { once: true })
// @ts-expect-error
})(document, Joomla, Vimeo);
