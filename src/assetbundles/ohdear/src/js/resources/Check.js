import LocalDate from "../helpers/LocalDate";

const VALID_TYPES = [
    'uptime',
    'broken_links',
    'mixed_content',
    'certificate_health',
    'certificate_transparency',
    'performance'
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
            throw new Error(`Invalid check type ${type}`);
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
        switch (this.type) {
            case 'uptime':
                return '/admin/oh-dear/uptime';
            case 'broken_links':
                return '/admin/oh-dear/broken-links';
            case 'mixed_content':
                return '/admin/oh-dear/mixed-content';
            case 'certificate_health':
                return '/admin/oh-dear/certificate-health';
            case 'certificate_transparency':
                return '/admin/oh-dear/certificate-health';
            default:
                return null;
        }
    }
}
