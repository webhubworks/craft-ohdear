<template>
    <div>

        <h2>Check details</h2>

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
            </tbody>
        </table>

        <h2>{{ $t('Mixed Content') }}</h2>

        <div v-if="loadingMixedContent" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 74px;">
            <loader></loader>
        </div>

        <div class="oh-w-full oh-flex oh-justify-center oh-flex-wrap oh-py-8" v-if="!mixedContentItems.length && !loadingMixedContent">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-w-32 oh-h-32"><path class="oh-fill-current oh-text-gray-300" d="M19.48 13.03l-.02-.03a1 1 0 1 1 1.75-.98A6 6 0 0 1 16 21h-4a6 6 0 1 1 0-12h1a1 1 0 0 1 0 2h-1a4 4 0 1 0 0 8h4a4 4 0 0 0 3.48-5.97z"/><path class="oh-fill-current oh-text-gray-500" d="M4.52 10.97l.02.03a1 1 0 1 1-1.75.98A6 6 0 0 1 8 3h4a6 6 0 1 1 0 12h-1a1 1 0 0 1 0-2h1a4 4 0 1 0 0-8H8a4 4 0 0 0-3.48 5.97z"/></svg>
            <p class="oh-w-full oh-text-center">{{ $t('Nice work, all of your contents are safe and sound.') }}</p>
        </div>

        <table class="data fullwidth" v-if="mixedContentItems.length && !loadingMixedContent">
            <thead>
            <tr>
                <th>Element</th>
                <th>Mixed Content</th>
                <th>Content Type</th>
                <th>Status</th>
                <th scope="col" data-attribute="link" data-icon="world" title="Link"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="mixedContent in mixedContentItems">
                <td>
                    <div v-if="mixedContent.element" class="element small hasstatus" :title="mixedContent.element.title">
                        <span :class="`status ${mixedContent.element.status}`"></span>
                        <div class="label">
                            <span class="title"><a :href="mixedContent.element.cpEditUrl">{{mixedContent.element.title}}</a></span>
                        </div>
                    </div>
                    <div v-else class="element small hasstatus">
                        <span class="status disabled"/>
                        <div class="label">
                            <span class="title">Not found</span>
                        </div>
                    </div>
                </td>
                <td>
                    <a :href="mixedContent.mixedContentUrl" :title="mixedContent.mixedContentUrl" target="_blank">{{ mixedContent.shortMixedContentUrl }}</a>
                </td>
                <td>
                    <badge color="gray"
                           :label="mixedContent.elementName"
                           class="oh-uppercase"
                           :dotless="true"/>
                </td>
                <td>
                    <badge v-if="mixedContent.isPotentiallyFixedSince(check.latestRunEndedAt)"
                           color="teal"
                           label="Pending"
                           :dotless="true">
                        <info-icon class="oh-ml-1 oh-mt-0.5">This mixed content item may have been fixed because the element has been updated since the last check.<br>Wait until the next check or request a new run.</info-icon>
                    </badge>
                    <badge v-else color="red" label="Insecure" :dotless="true"/>
                </td>
                <td data-title="Link" data-attr="link">
                    <a :href="mixedContent.foundOnUrlPathname" title="Visit webpage where the broken link was found" rel="noopener" target="_blank" data-icon="world"/>
                </td>
            </tr>
            </tbody>
        </table>

    </div>
</template>

<script>
    import Api from "../../helpers/Api";
    import {fetchesSite, hasCheck} from "../../helpers/Mixins";
    import MixedContentItem from "../../resources/MixedContentItem";

    export default {
        name: "MixedContent",
        mixins: [fetchesSite, hasCheck],
        data() {
            return {
                checkType: 'mixed_content',
                loadingMixedContent: true,
                mixedContentItems: [],
            }
        },
        mounted() {
            this.fetchMixedContent();
        },
        computed: {
            internalMixedContent() {
                return this.mixedContentItems.filter(link => {
                    try {
                        return new URL(link.crawledUrl).hostname === new URL(link.foundOnUrl).hostname;
                    } catch (e) {
                        return true;
                    }
                })
            },
            externalMixedContent() {
                return this.mixedContentItems.filter(link => {
                    try {
                        return new URL(link.crawledUrl).hostname !== new URL(link.foundOnUrl).hostname;
                    } catch (e) {
                        return false;
                    }
                })
            }
        },
        methods: {
            fetchMixedContent() {
                return Api.getMixedContent()
                    .then(response => {
                        this.loadingMixedContent = false;
                        this.mixedContentItems = response.data.mixedContentItems.map(item => MixedContentItem.fromJson(item));
                    })
            }
        }
    }
</script>

<style scoped>

</style>
