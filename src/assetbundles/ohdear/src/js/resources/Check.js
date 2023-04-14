import LocalDate from "../helpers/LocalDate";
import Api from "../helpers/Api";
import Typo from "../helpers/Typo";

export const VALID_TYPES = [
    'uptime',
    'broken_links',
    'mixed_content',
    'certificate_health',
    // 'certificate_transparency',
    'performance',
    // 'cron',
    // 'dns',
    'application_health',
];

export default class Check {

    constructor(enabled, id, label, latestRunEndedAt, latestRunResult, type) {
        this.enabled = enabled;
        this.id = id;
        this.label = label;
        this.latestRunEndedAt = LocalDate(latestRunEndedAt);
        this.latestRunResult = latestRunResult;
        this.type = type;

        if (!VALID_TYPES.includes(type)) {
            // console.warn(`Unknown check type ${type}`);
        }
    }

    static fromJson(json) {
        return new this(
            json.enabled,
            json.id,
            json.label,
            json.latestRunEndedAt,
            json.latestRunResult,
            json.type,
        );
    }

    /**
     * Determines the CP URL to the report depending on
     * check type, returns null if not applicable.
     *
     * @return {string|null}
     */
    get reportUrl() {
        const cpTriggerPrefix = window.Craft.cpTrigger !== null ? `/${window.Craft.cpTrigger}` : '';
        switch (this.type) {
            case 'uptime':
                return `${cpTriggerPrefix}/ohdear/uptime`;
            case 'performance':
                return `${cpTriggerPrefix}/ohdear/performance`;
            case 'broken_links':
                return `${cpTriggerPrefix}/ohdear/broken-links`;
            case 'mixed_content':
                return `${cpTriggerPrefix}/ohdear/mixed-content`;
            case 'certificate_health':
                return `${cpTriggerPrefix}/ohdear/certificate-health`;
            case 'certificate_transparency':
                return `${cpTriggerPrefix}/ohdear/certificate-health`;
            case 'application_health':
                return `${cpTriggerPrefix}/ohdear/application-health`;
            default:
                return null;
        }
    }

    get badgeHelpText() {
        switch (this.type) {
            case 'performance':
                return 'Average performance in last 15 minutes.';
            default:
                return null;
        }
    }

    get hasInlineMetric() {
        return ['performance', 'cron'].includes(this.type);
    }

    get inlineMetric() {
        switch (this.type) {
            case 'performance':
                return Api.getCurrentPerformance().then(response => {
                    if (Number.isInteger(response.data.currentPerformance)) {
                        return response.data.currentPerformance + ' ms';
                    }
                    return 'OK';
                });
            case 'cron':
                return Api.getCronChecks().then(response => {
                    if (response.data.cronChecks.length === 0) {
                        return Typo.checks[this.type].badge.empty;
                    }
                    this.latestRunResult === 'succeeded' ?
                        Typo.checks[this.type].badge.good :
                        Typo.checks[this.type].badge.bad;
                });
            default:
                return null;
        }
    }
}
