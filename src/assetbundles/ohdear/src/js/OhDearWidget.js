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
import Translator from "./Translator";

Vue.component('widget', require('./components/Widget').default);
Vue.component('badge', require('./components/Badge').default);
Vue.component('check-badge', require('./components/CheckBadge').default);
Vue.component('loader', require('./components/Loader').default);

/**
 * DayJs Plugins
 */
DayJs.extend(require('dayjs/plugin/utc'));

Vue.mixin({
    methods: {
        $t(key, replace = {}) {
            return Translator(window.OhDear.translations, key, replace)
        },
    },
})

const dashboardGrid = document.getElementById('dashboard-grid');
if (dashboardGrid) {
    new Vue({
        el: dashboardGrid
    });
}
