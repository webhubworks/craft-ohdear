import LocalDate from "../helpers/LocalDate";

export default class Element {
    /**
     * @param cpEditUrl
     * @param dateUpdated
     * @param id
     * @param status
     * @param title
     */
    constructor(cpEditUrl, dateUpdated, id, status, title) {
        this.cpEditUrl = cpEditUrl;
        this.dateUpdated = LocalDate(dateUpdated);
        this.id = id;
        this.status = status;
        this.title = title;
    }

    static fromJson(json) {
        return new this(json.cpEditUrl, json.dateUpdated, json.id, json.status, json.title);
    }
}
