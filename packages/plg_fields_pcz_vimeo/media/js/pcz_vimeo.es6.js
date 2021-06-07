// xts-check

/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.pcz_vimeo
 *
 * @copyright   Copyright (C) 2021 Piotr Konieczny. All rights reserved.
 * @license     GNU General Public License version 3 or later; see http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @typedef { import('@vimeo/player').default } Vimeo
 * @typedef { import('../../system/js/core-uncompressed.js').default } Joomla
 */

((document, Joomla) => {
  'use strict';

  /**
   *
   * @param {string} vimeoId
   * @param {string} url
   * @param {function} onSuccess
   * @return
   */
  const request = (url, vimeoId, onSuccess) =>
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
      onSuccess: (responseText) => {
        /** @type {{success: boolean, message: string|null, messages: null, data: any[]}} */
        const response = JSON.parse(responseText)

        if (response.success)
          onSuccess(response.data)
        }
      })

  const onBoot = () => {
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
    const iframes = document.querySelectorAll('[data-plg_fields_pcz_vimeo]')

    for (const iframe of iframes.values()) {
      /** @type {{vimeoId: string, logEnded: boolean}} */
      const params = JSON.parse(iframe.dataset.plg_fields_pcz_vimeo)
      const player = new Vimeo.Player(iframe)

      if (params.logEnded) {
        const handleVideoEnded = () =>
          request(options.uri, params.vimeoId, () =>
            player.off('ended', handleVideoEnded)
          )

        player.on('ended', handleVideoEnded)
      }
    }
  }

  document.addEventListener('DOMContentLoaded', onBoot, { once: true })
})(document, Joomla);
