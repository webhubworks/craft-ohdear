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

import '../css/OhDear.css';
import 'floating-vue/dist/style.css';
import { createApp } from 'vue';
import { vTooltip } from 'floating-vue';
import DayJs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import isoWeek from 'dayjs/plugin/isoWeek';
import relativeTime from 'dayjs/plugin/relativeTime';
import isToday from 'dayjs/plugin/isToday';
import Translator from './Translator';
import CheckPermissions from './CheckPermissions';
import Card from './components/Card.vue';
import Overview from './components/pages/Overview.vue';
import Uptime from './components/pages/Uptime.vue';
import Performance from './components/pages/Performance.vue';
import PerformanceChart from './components/PerformanceChart.vue';
import BrokenLinks from './components/pages/BrokenLinks.vue';
import MixedContent from './components/pages/MixedContent.vue';
import CertificateHealth from './components/pages/CertificateHealth.vue';
import ApplicationHealth from './components/pages/ApplicationHealth.vue';
import HttpStatusBadge from './components/HttpStatusBadge.vue';
import Badge from './components/Badge.vue';
import CheckBadge from './components/CheckBadge.vue';
import CheckBody from './components/CheckBody.vue';
import Loader from './components/Loader.vue';
import InfoIcon from './components/InfoIcon.vue';

DayJs.extend(customParseFormat);
DayJs.extend(utc);
DayJs.extend(timezone);
DayJs.extend(localizedFormat);
DayJs.extend(isoWeek);
DayJs.extend(relativeTime);
DayJs.extend(isToday);

// Keyed by the data-page attribute of #ohdear-app in the page templates.
const pages = {
    'overview': Overview,
    'uptime': Uptime,
    'performance': Performance,
    'broken-links': BrokenLinks,
    'mixed-content': MixedContent,
    'certificate-health': CertificateHealth,
    'application-health': ApplicationHealth,
};

const el = document.getElementById('ohdear-app');
if (el) {
    const app = createApp(pages[el.dataset.page]);

    app.component('card', Card);
    app.component('performance-chart', PerformanceChart);
    app.component('http-status-badge', HttpStatusBadge);
    app.component('badge', Badge);
    app.component('check-badge', CheckBadge);
    app.component('check-body', CheckBody);
    app.component('loader', Loader);
    app.component('info-icon', InfoIcon);
    app.directive('tooltip', vTooltip);

    app.config.globalProperties.$t = (key, replace = {}) => Translator(window.OhDear.translations, key, replace);
    app.config.globalProperties.$can = (ability, check) => CheckPermissions(window.OhDear.permissions, ability, check);

    app.mount(el);
}
