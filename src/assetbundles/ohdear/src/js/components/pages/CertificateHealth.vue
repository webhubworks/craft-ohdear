<template>
    <div>

        <div v-if="!certificateHealth" class="oh-flex oh-w-full oh-py-64 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="certificateHealth">
            <h2>Certificate checks</h2>
            <table class="data collapsible">
                <tbody>
                <tr v-for="certificateCheck in certificateHealth.certificateChecks">
                    <th class="light">{{certificateCheck.label}}</th>
                    <td>
                        <svg v-if="certificateCheck.passed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-h-6 oh-w-6">
                            <circle class="oh-text-green-200 oh-fill-current" cx="12" cy="12" r="10"/>
                            <path class="oh-text-green-600 oh-fill-current" d="M10 14.59l6.3-6.3a1 1 0 0 1 1.4 1.42l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 0 1 1.4-1.42l2.3 2.3z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-h-6 oh-w-6">
                            <circle cx="12" cy="12" r="10" class="oh-text-red-200 oh-fill-current"/>
                            <path class="oh-text-red-600 oh-fill-current" d="M13.41 12l2.83 2.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 1 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12z"/>
                        </svg>
                    </td>
                </tr>
                </tbody>
            </table>
            <h2>Certificate details</h2>
            <table class="data collapsible">
                <tbody>
                <tr>
                    <th class="light">Issuer</th>
                    <td>{{certificateHealth.certificateDetails.issuer}}</td>
                </tr>
                <tr>
                    <th class="light">Valid form</th>
                    <td>{{certificateHealth.certificateDetails.valid_from|localDate('llll')}}</td>
                </tr>
                <tr>
                    <th class="light">Valid until</th>
                    <td>{{certificateHealth.certificateDetails.valid_until|localDate('llll')}}</td>
                </tr>
                </tbody>
            </table>
            <h2>Certificate chain</h2>
            <table class="data collapsible">
                <tbody>
                <tr>
                    <th class="light">Valid certificate chain</th>
                    <td>
                        <svg v-if="certificateHealth.certificateChecks.find(check => {return check.type === 'invalidChain'}).passed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-h-6 oh-w-6">
                            <circle class="oh-text-green-200 oh-fill-current" cx="12" cy="12" r="10"/>
                            <path class="oh-text-green-600 oh-fill-current" d="M10 14.59l6.3-6.3a1 1 0 0 1 1.4 1.42l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 0 1 1.4-1.42l2.3 2.3z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-h-6 oh-w-6">
                            <circle cx="12" cy="12" r="10" class="oh-text-red-200 oh-fill-current"/>
                            <path class="oh-text-red-600 oh-fill-current" d="M13.41 12l2.83 2.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 1 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12z"/>
                        </svg>
                    </td>
                </tr>
                <tr v-for="(issuer, index) in certificateHealth.certificateChainIssuers">
                    <th class="light">#{{index + 1}}</th>
                    <td>
                        Issuer: {{issuer.split(',')[1]}}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import Api from "../../helpers/Api";
    import {localDateFilter} from "../../helpers/Mixins";

    export default {
        name: "CertificateHealth",
        mixins: [localDateFilter],
        data() {
            return {
                certificateHealth: null
            }
        },
        mounted() {
            this.fetchCertificateHealth();
        },
        methods: {
            fetchCertificateHealth() {
                return Api.getCertificateHealth().then(response => {
                    this.certificateHealth = response.data.certificateHealth;
                })
            }
        }
    }
</script>

<style scoped>

</style>
