<template>
    <div>
        <div class="oh-grid md:oh-grid-cols-2 oh-gap-4 oh-max-w-6xl" v-if="site">
            <card v-for="check in supportedChecks"
                  v-if="$can('view', check.type)"
                  :check="check"
                  :siteLoading="loading"
                  @should-refresh-site="fetchSite"
                  :key="check.type"
            />
        </div>
        <div class="oh-flex oh-pt-48 oh-justify-center oh-max-w-6xl" v-show="!site">
            <loader class="oh-w-6 oh-h-6"/>
        </div>
    </div>
</template>

<script>
import Api from "../../helpers/Api";
import Site from "../../resources/Site";
import {VALID_TYPES} from "../../resources/Check";

export default {
    name: "Cards",
    data() {
        return {
            site: null,
            loading: true
        }
    },
    mounted() {
        this.fetchSite().then(() => {
            setInterval(this.fetchSite, 7500);
        });
    },
    computed: {
        supportedChecks() {
            return this.site.checks.filter(check => {
                return VALID_TYPES.includes(check.type);
            });
        }
    },
    methods: {
        fetchSite() {
            this.loading = true;
            return Api.getSite().then(response => {
                this.site = Site.fromJson(response.data.site);
                this.loading = false;
            });
        },
    }
}
</script>
