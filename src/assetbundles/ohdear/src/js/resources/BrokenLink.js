import Element from './Element';
import DayJs from 'dayjs';
import LocalDate from "../helpers/LocalDate";

export default class BrokenLink {
    constructor(crawledUrl, foundOnUrl, statusCode, element) {
        this.crawledUrl = crawledUrl;
        this.foundOnUrl = foundOnUrl;
        this.statusCode = statusCode || 'No response';
        if (element instanceof Element) {
            this.element = element;
        } else {
            this.element = Element.fromJson(element);
        }
    }

    get shortCrawledUrl() {
        return this.crawledUrl.substr(0, 50) + '...';
    }

    get foundOnUrlPathname() {
        try {
            return new URL(this.foundOnUrl).pathname;
        } catch (e) {
            return "";
        }
    }

    static fromJson(json) {
        return new this(json.crawledUrl, json.foundOnUrl, json.statusCode, json.element);
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
        if (!DayJs.isDayjs(latestRunDate)) {
            latestRunDate = LocalDate(latestRunDate);
        }
        return this.element.dateUpdated.isAfter(latestRunDate);
    }
}
