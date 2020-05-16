<template>
    <div>

        <div id="settings-apiToken-field" class="field">
            <div class="heading"><label id="settings-apiToken-label" for="settings-apiToken">Oh Dear API Token</label>
                <div class="instructions"><p>Please enter your Oh Dear API token. You can create a token <a href="https://ohdear.app/user-settings/api" rel="noopener" target="_blank">here</a>.</p></div>
            </div>
            <div class="input ltr">
                <input type="text" id="settings-apiToken" name="settings[apiToken]" v-model.trim="tokenValue" autocomplete="off" class="text fullwidth">
            </div>
        </div>

        <input type="hidden" :value="selectedSiteId" id="settings-selectedSiteId" name="settings[selectedSiteId]">

        <div v-if="loading" class="oh-w-full oh-p-16 oh-flex oh-justify-center">
            <loader/>
        </div>

        <div v-if="!loading && sites.length && tokenValue.length">

            <p class="oh-mb-4">You can choose from the sites of all teams that your personal Oh Dear account belongs to:</p>

            <table class="data fullwidth collapsible">
                <thead>
                <tr>
                    <th>Site</th>
                    <th>Created at</th>
                    <th>Latest run</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="site in sites">
                    <th>
                        <a :href="site.url" class="go" target="_blank" rel="noopener">{{ site.sortUrl }}</a>
                    </th>
                    <td>{{ site.createdAt|localDate('lll') }}</td>
                    <td>{{ site.latestRunDate|localDate('lll') }}</td>
                    <td class="oh-flex oh-justify-end">
                        <button @click.prevent="setSiteId(site.id)"
                                :title="site.id"
                                :disabled="parseInt(site.id) === parseInt(selectedSiteId)"
                                :class="{'disabled': parseInt(site.id) === parseInt(selectedSiteId)}"
                                class="btn small">
                            {{parseInt(site.id) === parseInt(selectedSiteId) ? 'Selected' : 'Select this site' }}
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import Api from '../helpers/Api';
    import {localDateFilter} from '../helpers/Mixins';
    import {sortBy} from 'lodash';

    export default {
        name: 'TokenField',
        mixins: [localDateFilter],
        props: {
            token: {
                type: String,
                default: ''
            },
            siteId: {
                type: String,
                default: ''
            }
        },
        data() {
            return {
                sites: [],
                loading: false,
                tokenValue: '',
                selectedSiteId: ''
            }
        },
        created() {
            this.selectedSiteId = this.siteId;
            this.tokenValue = this.token;
        },
        mounted() {
            if (this.tokenIsValid) {
                this.fetchSites();
            }
        },
        computed: {
            tokenIsValid() {
                return validator.tokenIsValid(this.tokenValue);
            },
            isPristine() {
                return this.token.trim().length === 0 || this.siteId.trim().length === 0;
            }
        },
        watch: {
            tokenValue(token) {
                if (validator.tokenIsValid(token)) {
                    if (this.isPristine) {
                        this.fetchSites(token);
                    } else {
                        this.fetchSites();
                    }
                }
                if (token.trim().length === 0) {
                    this.selectedSiteId = '';
                }
            }
        },
        methods: {
            fetchSites(token = null) {
                this.loading = true;
                Api.getSites(token).then(response => {
                    this.loading = false;
                    this.sites = sortBy(response.data.sites, ['sortUrl']);
                })
            },
            setSiteId(id) {
                this.selectedSiteId = id;
            }
        }
    }
    const validator = {
        tokenIsValid(token) {
            return token.trim().length === 60;
        }
    }
</script>
