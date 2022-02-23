<template>
    <div>

        <h2>{{ $t('Latest health check results') }}</h2>

        <div v-if="!healthChecks" class="oh-flex oh-w-full oh-py-32 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="healthChecks">
            <p class="oh-mb-4">This is a list of the application health checks that are currently running for your site.
                Click on one of them to display history results.</p>
            <div
                class="oh-divide-y oh-divide-dashed oh-divide-gray-300 oh-border-t oh-border-dashed oh-border-gray-300">
                <div v-for="check in healthChecks" :key="check.id+'-check'">
                    <div class="oh-flex oh-py-4">
                        <div class="oh-pl-2 oh-flex oh-items-start oh-min-w-[5rem]">
                            <badge :dotless="true" :label="check.status.toUpperCase()"
                                   :color="getBadgeColor(check.status)"/>
                        </div>
                        <div>
                            <button class="oh-ml-1 hover:oh-underline"
                                    title="Toggle historic check results" @click.prevent="toggleHistory(check.id)">
                                {{ check.label }}
                            </button>
                            <span class="oh-ml-1 oh-text-gray-500">{{ check.message || check.shortSummary }}</span>
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
import {localDateFilter} from "../../helpers/Mixins";

export default {
    name: "ApplicationHealth",
    mixins: [localDateFilter],
    data() {
        return {
            healthChecks: null,
            history: {},
            loadingForCheckId: null,
        }
    },
    mounted() {
        this.fetchApplicationHealth();
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
