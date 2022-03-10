<template>
    <div>

        <h2>{{ $t('Check details') }}</h2>

        <div v-if="loadingSite" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 74px;">
            <loader/>
        </div>

        <table class="data collapsible" v-if="!loadingSite">
            <tbody>
            <tr>
                <th class="light">{{ $t('Status') }}</th>
                <td>
                    <check-badge :check="check"/>
                </td>
            </tr>
            <tr>
                <th class="light">{{ $t('Last run') }}</th>
                <td>{{ lastRun }}</td>
            </tr>
            <tr v-if="lastSuccessfulRun">
                <th class="light">{{ $t('Last successful run') }}</th>
                <td><badge v-if="lastSuccessfulRun.warning" :dotless="true" label="!" color="red" :round="true" class="oh-mr-2" />{{ lastSuccessfulRun.label }}</td>
            </tr>
            </tbody>
        </table>

        <h2>{{ $t('History') }}</h2>

        <div v-if="!healthChecks" class="oh-flex oh-w-full oh-py-32 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="healthChecks">
            <p class="oh-mb-4">{{ $t('This is a list of the application health checks that are currently running for your site. Click on one of them to display history results.') }}</p>

            <div
                class="oh-divide-y oh-divide-dashed oh-divide-gray-300 oh-border-t oh-border-dashed oh-border-gray-300">
                <div v-for="check in healthChecks" :key="check.id+'-check'">
                    <div class="oh-flex oh-py-4">
                        <div class="oh-pl-2 oh-flex oh-items-start oh-min-w-[5rem]">
                            <badge :dotless="true" :label="check.status.toUpperCase()"
                                   :color="getBadgeColor(check.status)"/>
                        </div>
                        <div>
                            <button class="oh-ml-1 oh-underline hover:oh-text-gray-900"
                                    :title="$t('Toggle historic check results')" @click.prevent="toggleHistory(check.id)">
                                {{ $t(check.label) }}
                            </button>
                            <span class="oh-ml-1 oh-text-gray-500">{{ $t(check.message) || $t(check.shortSummary) }}</span>
                            <div class="oh-pl-1 oh-pt-4" v-show="loadingForCheckId === check.id">
                                <loader/>
                            </div>
                        </div>
                    </div>
                    <div v-if="history[check.id].open" class="oh-pb-3 oh-space-y-4">
                        <div class="oh-flex" :key="result.id+'-result'"
                             v-for="result in history[check.id].results">
                            <div class="oh-px-2 oh-ml-1 oh-flex oh-items-start oh-min-w-[5rem]">
                                <badge :dotless="true" :label="result.status.toUpperCase()"
                                       :color="getBadgeColor(result.status)" :small="true"/>
                            </div>
                            <span class="oh-ml-1">{{ getFromNowDate(result.detectedAt) }}</span>
                            <span class="oh-ml-2 oh-text-gray-500">{{ result.message || result.shortSummary }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Api from "../../helpers/Api";
import LocalDate from '../../helpers/LocalDate';
import {localDateFilter, fetchesSite} from "../../helpers/Mixins";
import DayJs from "dayjs";

export default {
    name: "ApplicationHealth",
    mixins: [localDateFilter, fetchesSite],
    data() {
        return {
            checkType: 'application_health',
            healthChecks: null,
            history: {},
            loadingForCheckId: null,
        }
    },
    mounted() {
        this.fetchApplicationHealth();
    },
    computed: {
        lastSuccessfulRun() {
            if (!this.healthChecks || !this.healthChecks.length) {
                return null;
            }
            let lastRun = DayJs.unix(this.healthChecks.map(check => DayJs.utc(check.updatedAt).unix()).sort().reverse()[0]);
            return {
                warning: lastRun.isBefore(DayJs().subtract(1, 'day')),
                label: this.$t('{fromNow} on {date}', {
                    fromNow: lastRun.fromNow(),
                    date: lastRun.format('llll'),
                })
            };
        }
    },
    methods: {
        getFromNowDate(date) {
            return LocalDate(date).fromNow();
        },
        getBadgeColor(status) {
            switch (status) {
                case 'ok':
                    return 'green';
                    break;
                case 'warning':
                    return 'yellow';
                    break;
                case 'failed':
                    return 'red';
                    break;
                case 'crashed':
                    return 'red';
                    break;
                default:
                    return 'gray';
            }
        },
        toggleHistory(checkId) {
            if (this.history[checkId].open) {
                this.history = {
                    ...this.history,
                }
                this.history[checkId] = {
                    open: false,
                }
                return;
            }
            this.loadingForCheckId = checkId;
            return Api.getApplicationHealthCheckResults(checkId).then(response => {
                this.history[checkId].results = response.data.applicationHealthCheckResults;
                this.history[checkId].open = true;
                this.loadingForCheckId = null;
            })
        },
        fetchApplicationHealth() {
            return Api.getApplicationHealthChecks().then(response => {
                this.healthChecks = response.data.applicationHealthChecks;
                this.healthChecks.forEach(check => this.history[check.id] = {
                    open: false,
                })
            })
        }
    }
}
</script>
