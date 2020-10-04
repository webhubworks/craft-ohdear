<template>
    <div class="ohdear-widget">
        <loader class="ohdear-widget__loader" v-if="loadingUptime && loadingSite"></loader>
        <div class="ohdear-widget__stats" v-if="!loadingUptime && !loadingSite">
            <dl class="ohdear-widget__stat ohdear-widget__stat--numeric" v-if="enabledChecks.includes('uptime') && !loadingUptime && uptimeCheck">
                <dt>Uptime {{periodLabel}}</dt>
                <dl>
                    <a title="Go to uptime report" :href="uptimeCheck.reportUrl">{{ averageUptime }}%</a>
                </dl>
            </dl>
            <dl class="ohdear-widget__stat ohdear-widget__stat--numeric" v-if="enabledChecks.includes('uptime') && !loadingUptime">
                <dt>Downtime</dt>
                <dl>{{ downtime|friendlyDuration }}</dl>
            </dl>
            <dl v-if="enabledChecks.includes('broken_links') && !loadingSite" class="ohdear-widget__stat">
                <dt>{{brokenLinksCheck.label}}</dt>
                <dl>
                    <a :href="brokenLinksCheck.reportUrl">
                        <check-badge :check="brokenLinksCheck"/>
                    </a>
                </dl>
            </dl>
            <dl v-if="enabledChecks.includes('mixed_content') && !loadingSite" class="ohdear-widget__stat">
                <dt>{{mixedContentCheck.label}}</dt>
                <dl>
                    <a :href="mixedContentCheck.reportUrl">
                        <check-badge :check="mixedContentCheck"/>
                    </a>
                </dl>
            </dl>
            <dl v-if="enabledChecks.includes('certificate_health') && !loadingSite" class="ohdear-widget__stat">
                <dt>{{certificateHealthCheck.label}}</dt>
                <dl>
                    <a :href="certificateHealthCheck.reportUrl">
                        <check-badge :check="certificateHealthCheck"/>
                    </a>
                </dl>
            </dl>
        </div>
    </div>
</template>

<script>

    import DayJs from 'dayjs';
    import Api from '../helpers/Api';
    import Site from "../resources/Site";
    import Loader from './Loader';

    function average(array) {
        let sum = 0;
        let count = array.length;
        for (let i = 0; i < count; i++) {
            sum = sum + array[i];
        }
        return sum / count;
    }

    export default {
        components: {
            Loader
        },
        data() {
            return {
                site: null,
                uptime: [],
                loadingSite: true,
                loadingUptime: true,
            }
        },
        props: {
            checks: {
                type: Array,
                required: true
            },
            period: {
                type: String,
                required: true
            }
        },
        computed: {
            periodLabel() {
                switch (this.period) {
                    case 'hour':
                        return '(24 hours)';
                    case 'day':
                        return '(last month)';
                    case 'month':
                        return '(last year)';
                    default:
                        return '';
                }
            },
            uptimeCheck() {
                return this.getCheckByType('uptime');
            },
            brokenLinksCheck() {
                return this.getCheckByType('broken_links');
            },
            mixedContentCheck() {
                return this.getCheckByType('mixed_content');
            },
            certificateHealthCheck() {
                return this.getCheckByType('certificate_health');
            },
            enabledChecks() {
                let enabledChecks = [];
                ['uptime', 'broken_links', 'mixed_content', 'certificate_health'].forEach(checkType => {
                    if (this.checks.includes(checkType)) {
                        enabledChecks.push(checkType);
                    }
                });
                return enabledChecks;
            },
            periodStart() {
                switch (this.period) {
                    case 'hour':
                        return DayJs().subtract(25, 'hour');
                    case 'day':
                        return DayJs().subtract(1, 'month');
                    case 'month':
                        return DayJs().subtract(1, 'year');
                    default:
                        return null;
                }
            },
            minutesInPeriod() {
                if (!this.uptime.length) {
                    return 0;
                }
                let firstRecordDate = DayJs(this.uptime[0].datetime, 'YYYY-MM-DD HH:mm:ss');
                return DayJs().diff(firstRecordDate, 'minute', true);
            },
            averageUptime() {
                return Math.floor(average(this.uptimePercentages) * 10) / 10;
            },
            downtime() {
                return Math.round(this.minutesInPeriod * (100 - this.averageUptime) / 100);
            },
            uptimePercentages() {
                return this.uptime.map(record => {
                    return record.uptimePercentage;
                });
            }
        },
        filters: {
            /**
             * @param minutes
             * @returns {string}
             */
            friendlyDuration(minutes) {
                if (parseInt(minutes) === 0) {
                    return "-";
                }
                let hours = Math.trunc(minutes / 60);
                let remainingMinutes = minutes % 60;
                let duration = "";
                if (hours > 0) {
                    duration = `${hours}h`;
                }
                if (remainingMinutes > 0) {
                    duration += `${remainingMinutes}m`;
                }
                return duration;
            }
        },
        mounted() {
            this.fetchSite();
            this.fetchUptime();
        },
        methods: {
            /**
             * @param {string} type
             * @return {Object|undefined}
             */
            getCheckByType(type) {
                if (this.site === null) {
                    return undefined;
                }
                return this.site.checks.find(check => check.type === type);
            },
            fetchSite() {
                return Api.getSite().then(response => {
                    this.site = Site.fromJson(response.data.site);
                    this.loadingSite = false;
                });
            },
            fetchUptime() {
                const startedAt = this.periodStart.format('YYYYMMDDHHmmss');
                const endedAt = DayJs().format('YYYYMMDDHHmmss');
                return Api.getUptime(startedAt, endedAt, this.period)
                    .then(response => {
                        this.uptime = response.data.uptime;
                        this.loadingUptime = false;
                    })
            },
        }
    }
</script>
