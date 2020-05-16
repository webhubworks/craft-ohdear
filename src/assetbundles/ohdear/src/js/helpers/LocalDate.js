import DayJs from 'dayjs';

require(`dayjs/locale/de`);
require(`dayjs/locale/en`);

// Set locale globally if it is supported, depending on user settings
if (window.Craft.language && ['de', 'en'].includes(window.Craft.language)) {
    DayJs.locale(window.Craft.language);
}

/**
 * @param {string|number|Date|DayJs|undefined} utcDateString
 * @return {*}
 * @constructor
 */
export default function LocalDate(utcDateString) {
    return DayJs.utc(utcDateString).local();
}
