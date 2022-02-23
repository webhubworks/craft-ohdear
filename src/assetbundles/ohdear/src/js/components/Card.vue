<template>
    <article class="oh-h-full oh-max-w-xl oh-p-4 oh-flex-col oh-flex oh-bg-white oh-border oh-shadow-craft oh-rounded">

        <header class="oh-mb-4 oh-flex oh-items-center oh-justify-between">

            <h2 class="oh-mb-0 oh-text-lg oh-font-medium">
                {{ $t(check.label) }}
            </h2>

            <check-badge :check="check"/>

        </header>

        <div class="oh-flex-1 oh-flex oh-flex-col oh-py-4 oh-border oh-border-r-0 oh-border-l-0 oh-border-gray-200 oh-border-solid oh-min-h-32">

            <div class="oh-flex-1 oh-mb-4 oh-text-sm">
                <check-body :check="check"/>
            </div>

            <div v-if="check.reportUrl">
                <div>
                    <a :href="check.reportUrl" class="oh-text-xs oh-text-gray-600 oh-underline">
                        {{ $t('View the latest report') }}
                    </a>
                </div>
            </div>

        </div>

        <section class="oh-mt-4 oh-flex oh-justify-between">

            <div class="oh-flex">
                <div class="lightswitch-field" v-if="checkEnabled !== null">
                    <div class="heading oh-sr-only">
                        <label :id="`ohdear-status-${check.type}`" class="">{{ check.label }} status</label>
                    </div>
                    <div class="input ltr">

                        <div class="lightswitch" :class="{'on': checkEnabled}" tabindex="0" :aria-labelledby="`ohdear-status-${check.type}`" role="checkbox" :aria-checked="checkEnabled">
                            <div class="lightswitch-container" @click.prevent="onToggleCheck">
                                <div class="handle"></div>
                            </div>
                            <input type="hidden" :bind="checkEnabled">
                        </div>

                    </div>
                </div>
                <loader class="oh-w-5 oh-h-5 oh-ml-1 oh-p-0.5" v-show="cardLoading"></loader>
            </div>

            <div class="oh-text-xs oh-text-gray-500" v-if="check.enabled">
                <button :class="{'oh-pointer-events-none': newRunRequested}" class="oh-outline-none hover:oh-underline" @click.prevent="requestNewRun" :disabled="newRunRequested">
                    {{ newRunLabel }}
                </button>
            </div>

        </section>
    </article>
</template>

<script>

    /**
     * Implementing optimistic UI pattern by switching
     * the toggle immediately before sending the actual
     * request to the API. After receiving the response
     * the toggle is set to the actual value, which is
     * hopefully the same.
     */

    import {hasCheck} from "../helpers/Mixins";
    import Check from "../resources/Check";

    export default {
        name: "Card",
        mixins: [hasCheck],
        props: {
            check: {
                type: Check,
                required: true
            },
            siteLoading: Boolean,
        },
        computed: {
            cardLoading() {
                return this.checkEnabled !== this.check.enabled;
            },
            newRunLabel() {
                return this.newRunRequested ? this.$t('New run requested') : this.$t('Request new run');
            },
        }
    }
</script>
