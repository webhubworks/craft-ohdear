import LocalDate from "../helpers/LocalDate";
import Check from "./Check";

export default class Site {
    /**
     * @param checks
     * @param createdAt
     * @param id
     * @param label
     * @param latestRunDate
     * @param sortUrl
     * @param summarizedCheckResult
     * @param teamId
     * @param updatedAt
     * @param url
     * @param usesHttps
     */
    constructor(checks, createdAt, id, label, latestRunDate, sortUrl, summarizedCheckResult, teamId, updatedAt, url, usesHttps) {
        this.checks = checks.map(check => Check.fromJson(check));
        this.id = id;
        this.label = label;
        this.updatedAt = LocalDate(updatedAt);
        this.createdAt = LocalDate(createdAt);
        this.latestRunDate = LocalDate(latestRunDate);
        this.sortUrl = sortUrl;
        this.summarizedCheckResult = summarizedCheckResult;
        this.teamId = teamId;
        this.url = url;
        this.usesHttps = usesHttps;
    }

    static fromJson(json) {
        return new this(
            json.checks,
            json.id,
            json.label,
            json.updatedAt,
            json.createdAt,
            json.latestRunDate,
            json.sortUrl,
            json.summarizedCheckResult,
            json.teamId,
            json.url,
            json.usesHttps,
        )
    }
}
