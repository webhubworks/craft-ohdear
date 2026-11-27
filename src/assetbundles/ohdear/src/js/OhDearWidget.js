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

import '../css/OhDearWidget.css';
import { createApp } from 'vue';
import DayJs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import Translator from './Translator';
import Widget from './components/Widget.vue';
import Badge from './components/Badge.vue';
import CheckBadge from './components/CheckBadge.vue';
import Loader from './components/Loader.vue';

DayJs.extend(utc);

// The widget body template calls this again after Craft re-renders a widget.
window.OhDear.mountWidgets = () => document.querySelectorAll('[data-init="ohdear-widget"]:not([data-mounted])').forEach(el => {
    el.setAttribute('data-mounted', '');

    const app = createApp(Widget, {
        period: el.dataset.period,
        checks: JSON.parse(el.dataset.checks),
    });

    app.component('badge', Badge);
    app.component('check-badge', CheckBadge);
    app.component('loader', Loader);

    app.config.globalProperties.$t = (key, replace = {}) => Translator(window.OhDear.translations, key, replace);

    app.mount(el);
});
window.OhDear.mountWidgets();
