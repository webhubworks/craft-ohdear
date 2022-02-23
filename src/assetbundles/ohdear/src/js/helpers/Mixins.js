import Api from './Api';
import DayJs from 'dayjs';
import {groupBy} from "lodash";

import LocalDate from "./LocalDate";
import Site from "../resources/Site";

const hasCheck = {
    methods: {
        onToggleCheck() {
            if (this.loading) {
                return;
            }
            this.checkEnabled = !this.checkEnabled;
            this.check.enabled ? this.disableCheck() : this.enableCheck();
        },
        disableCheck() {
            if (this.siteLoading) {
                return;
            }
            return Api.disableCheck(this.check.id).then(() => {
                this.$emit('should-refresh-site');
            });
        },
        enableCheck() {
            if (this.siteLoading) {
                return;
            }
            return Api.enableCheck(this.check.id).then(() => {
                this.$emit('should-refresh-site');
            });
        },
        requestNewRun() {
            this.newRunRequested = true;
            if (this.siteLoading) {
                return;
            }
            return Api.requestRun(this.check.id).then(response => {
                if (response.error) {
                    alert(response.error);
                    this.newRunRequested = false;
                    return;
                }
                this.$emit('should-refresh-site');
            });
        },
    },
    watch: {
        latestRunEndedAt: {
            handler: function (newDate, oldDate) {
                if (!newDate) {
                    return;
                }
                if (!newDate.isSame(oldDate)) {
                    this.newRunRequested = false;
                }
            },
            immediate: true
        },
        check: {
            handler: function () {
                this.checkEnabled = this.check.enabled;
                if (this.latestRunEndedAt !== this.check.latestRunEndedAt) {
                    this.latestRunEndedAt = this.check.latestRunEndedAt;
                }
            },
            deep: true,
            immediate: true
        }
    },
    data() {
        return {
            checkEnabled: null,
            latestRunEndedAt: null,
            newRunRequested: null,
        }
    }
};

const fetchesSite = {
    data() {
        return {
            site: null
        }
    },
    mounted() {
        this.fetchSite();
    },
    methods: {
        fetchSite() {
            return Api.getSite().then(response => {
                this.site = Site.fromJson(response.data.site);
            });
        },
    },
    computed: {
        loadingSite() {
            return this.site === null;
        },
        lastRun() {
            if (this.site === null) {
                return "";
            }
            const latestRun = LocalDate(this.check.latestRunEndedAt);
            return this.$t('{fromNow} on {date}', {
                fromNow: latestRun.fromNow(),
                date: latestRun.format('llll'),
            });
        },
        badgeColor() {
            if (!this.check.enabled) {
                return 'gray';
            }
            switch (this.check.latestRunResult) {
                case 'succeeded':
                    return 'green';
                case 'failed':
                    return 'red';
                default:
                    return 'green'
            }
        },
        check() {
            if (this.checkType === undefined) {
                console.warn('checkType data property is missing');
            }
            if (this.site === null) {
                return {};
            }
            return this.site.checks.find(check => {
                return check.type === this.checkType;
            })
        },
    }
};

const fetchesDowntime = {
    data() {
        return {
            periodStart: this.getMondayAYearAgo().format('YYYYMMDDHHmmss'),
            periodEnd: DayJs().format('YYYYMMDDHHmmss'),
        }
    },
    mounted() {
        this.fetchDowntime();
    },
    computed: {
        downtimeGroupedByDay() {
            return groupBy(this.downtime, downtime => {
                return downtime.startedAt.substr(0, 10);
            })
        },
    },
    methods: {
        /**
         * @return {DayJs}
         */
        getMondayAYearAgo() {
            let i = 365;
            let day;
            do {
                day = DayJs().subtract(i, 'day');
                i--;
            } while (day.isoWeekday() !== 1);
            return day;
        },
        fetchDowntime() {
            return Api.getDowntime(
                this.periodStart,
                this.periodEnd,
            )
                .then(response => {
                    this.downtime = response.data.downtime;
                })
        },
    }
};

const localDateFilter = {
    filters: {
        localDate(date, format = 'LLLL') {
            return LocalDate(date).format(format);
        }
    }
};

export {
    fetchesSite,
    fetchesDowntime,
    hasCheck,
    localDateFilter,
};
