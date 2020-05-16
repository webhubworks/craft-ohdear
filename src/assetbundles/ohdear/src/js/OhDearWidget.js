/**
 * Oh Dear plugin for Craft CMS
 *
 * Oh Dear JS
 *
 * @author    webhub GmbH
 * @copyright Copyright (c) 2019 webhub GmbH
 * @link      https://webhub.de
 * @package   OhDear
 * @since     1.0.0
 */

import Vue from 'vue';
import DayJs from "dayjs";

Vue.component('widget', require('./components/Widget').default);
Vue.component('badge', require('./components/Badge').default);
Vue.component('check-badge', require('./components/CheckBadge').default);

/**
 * DayJs Plugins
 */
DayJs.extend(require('dayjs/plugin/utc'));

let widgets = document.querySelectorAll('.ohdear-widget');
widgets.forEach(widget => {
    new Vue({
        el: widget
    });
});
