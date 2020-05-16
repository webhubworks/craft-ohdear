// TODO: Can this be replaced with the DayJs implementation?

import * as humanizeDuration from "humanize-duration";

const prettyTime = humanizeDuration.humanizer({
    spacer: '',
    delimiter: ' ',
    language: 'short',
    languages: {
        short: {
            y: () => 'y',
            mo: () => 'mo',
            w: () => 'w',
            d: () => 'd',
            h: () => 'h',
            m: () => 'm',
            s: () => 's',
            ms: () => 'ms',
        }
    }
});

export default prettyTime;
