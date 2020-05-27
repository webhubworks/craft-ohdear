import Element from './Element';
import DayJs from 'dayjs';
import LocalDate from "../helpers/LocalDate";

export default class MixedContentItem {
    constructor(mixedContentUrl, foundOnUrl, elementName, element) {
        this.mixedContentUrl = mixedContentUrl;
        this.foundOnUrl = foundOnUrl;
        this.elementName = elementName || 'unknown';
        if (element instanceof Element || element === null) {
            this.element = element;
        } else {
            this.element = Element.fromJson(element);
        }
    }

    get shortMixedContentUrl() {
        return this.mixedContentUrl.substr(0,50) + '...';
    }

    get foundOnUrlPathname() {
        try {
            return new URL(this.foundOnUrl).pathname;
        } catch (e) {
            return "";
        }
    }

    static fromJson(json) {
        return new this(json.mixedContentUrl, json.foundOnUrl, json.elementName, json.element);
    }

    /**
     * Returns true if the broken link's element updated date
     * is after the provided check run date. This means that
     * someone has potentially fixed the broken link in the
     * element but there may not have been a new check run
     * for broken links on the Oh Dear side of things, yo
     *
     * @param {DayJs|string} latestRunDate This date instance must be in local time!
     * @return {boolean}
     */
    isPotentiallyFixedSince(latestRunDate) {
        if (!this.element) {
            return false;
        }
        if(!DayJs.isDayjs(latestRunDate)) {
            latestRunDate = LocalDate(latestRunDate);
        }
        return this.element.dateUpdated.isAfter(latestRunDate);
    }
}
