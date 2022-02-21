<template>
    <div>
        <a v-if="!check.hasInlineMetric && check.reportUrl" :href="check.reportUrl">
            <badge :color="color" :label="label" :dotless="false"/>
        </a>

        <div class="oh-flex oh-items-center" v-if="check.hasInlineMetric">
            <loader class="oh-mr-1" v-if="loaderPosition === 'left'" v-show="loadingMetric"></loader>

            <badge v-if="!loadingMetric" :class="{'oh-opacity-0 oh-h-6': label === null}" :color="color" :label="label" :dotless="false"/>

            <loader class="oh-ml-1" v-if="loaderPosition === 'right'" v-show="loadingMetric"></loader>
        </div>
    </div>

</template>

<script>
import Typo from "../helpers/Typo";
import Check from "../resources/Check";

export default {
    name: "CheckBadge",
    props: {
        check: {
            type: Check,
            required: true
        },
        loaderPosition: {
            type: String,
            default: 'left',
        }
    },
    data() {
        return {
            label: null,
            loadingMetric: false,
            shouldAutoFetchInlineMetric: false,
        }
    },
    watch: {
        shouldAutoFetchInlineMetric(itShouldIndeed) {
            if (itShouldIndeed) {
                this.fetchInlineMetric();
            }
        },
        'check.enabled': {
            immediate: true,
            handler(newValue, oldValue) {
                if (newValue === undefined) {
                    return;
                }
                if (!newValue) {
                    this.shouldAutoFetchInlineMetric = false;
                    this.label = 'Disabled';
                    return;
                }
                if (!this.check.hasInlineMetric) {
                    this.setStaticLabel();
                    return;
                }
                if (!oldValue && newValue) {
                    this.fetchInlineMetric();
                    this.shouldAutoFetchInlineMetric = true;
                }
            }
        }
    },
    methods: {
        setStaticLabel() {
            this.label = this.check.latestRunResult === 'succeeded' ?
                this.$t(Typo.checks[this.check.type].badge.good) :
                this.$t(Typo.checks[this.check.type].badge.bad);
        },
        fetchInlineMetric() {
            this.loadingMetric = true;
            return this.check.inlineMetric.then(metric => {
                this.label = metric + ' ms';
                this.loadingMetric = false;
            });
        }
    },
    computed: {
        color() {
            if (!this.check.enabled) {
                return 'gray';
            }
            if (this.check.latestRunResult === null) {
                return 'green';
            }
            switch (this.check.latestRunResult) {
                case 'succeeded':
                    return 'green';
                case 'failed':
                    return 'red';
                case 'pending':
                    return 'yellow';
                case 'warning':
                    return 'yellow';
                default:
                    return 'green'
            }
        }
    }
}
</script>
