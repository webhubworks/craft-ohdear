<template>
    <div>
        <div class="oh-grid md:oh-grid-cols-2 oh-gap-4 oh-max-w-6xl" v-if="site">
            <card v-for="check in site.checks"
                  v-if="['uptime','broken_links','mixed_content','certificate_health','certificate_transparency'].includes(check.type)"
                  :check="check"
                  @request-new-run="requestNewRun"
                  @disable-check="disableCheck"
                  @enable-check="enableCheck"
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
                setInterval(this.fetchSite, 5000);
            });
        },
        methods: {
            fetchSite() {
                this.loading = true;
                return Api.getSite().then(response => {
                    this.site = Site.fromJson(response.data.site);
                    this.loading = false;
                });
            },
            disableCheck(checkId) {
                if (this.loading) {
                    return;
                }
                return Api.disableCheck(checkId).then(() => {
                    this.fetchSite();
                });
            },
            enableCheck(checkId) {
                if (this.loading) {
                    return;
                }
                return Api.enableCheck(checkId).then(() => {
                    this.fetchSite();
                });
            },
            requestNewRun(checkId) {
                if (this.loading) {
                    return;
                }
                return Api.requestRun(checkId).then(() => {
                    this.fetchSite();
                });
            },
        }
    }
</script>

<style scoped>

</style>
