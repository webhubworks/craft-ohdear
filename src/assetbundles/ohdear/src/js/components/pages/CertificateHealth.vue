<template>
    <div>

        <h2>{{ $t('Certificate checks') }}</h2>

        <div v-if="!certificateHealth" class="oh-flex oh-w-full oh-py-56 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="certificateHealth">
            <table class="data">
                <tbody>
                <tr v-for="certificateCheck in certificateHealth.certificateChecks">
                    <td>
                        <template v-if="certificateCheck.passed">
                            <span class="oh-sr-only">OK</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 class="oh-h-6 oh-w-6" title="OK">
                                <circle class="oh-text-green-200 oh-fill-current" cx="12" cy="12" r="10"/>
                                <path class="oh-text-green-600 oh-fill-current"
                                      d="M10 14.59l6.3-6.3a1 1 0 0 1 1.4 1.42l-7 7a1 1 0 0 1-1.4 0l-3-3a1 1 0 0 1 1.4-1.42l2.3 2.3z"/>
                            </svg>
                        </template>

                        <template v-if="!certificateCheck.passed">
                            <span class="oh-sr-only">Not OK</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="oh-h-6 oh-w-6"
                                 title="Not OK">
                                <circle cx="12" cy="12" r="10" class="oh-text-red-200 oh-fill-current"/>
                                <path class="oh-text-red-600 oh-fill-current"
                                      d="M13.41 12l2.83 2.83a1 1 0 0 1-1.41 1.41L12 13.41l-2.83 2.83a1 1 0 1 1-1.41-1.41L10.59 12 7.76 9.17a1 1 0 0 1 1.41-1.41L12 10.59l2.83-2.83a1 1 0 0 1 1.41 1.41L13.41 12z"/>
                            </svg>
                        </template>
                    </td>
                    <th class="light">{{ $t(certificateCheck.label) }}</th>
                </tr>
                </tbody>
            </table>
        </div>

        <h2>{{ $t('Certificate details') }}</h2>

        <div v-if="!certificateHealth" class="oh-flex oh-w-full oh-py-16 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="certificateHealth">
            <table class="data">
                <tbody>
                <tr>
                    <th class="light">{{ $t('Issuer') }}</th>
                    <td>{{ certificateHealth.certificateDetails.issuer }}</td>
                </tr>
                <tr>
                    <th class="light">{{ $t('Valid form') }}</th>
                    <td>{{ localDate(certificateHealth.certificateDetails.valid_from, 'llll') }}</td>
                </tr>
                <tr>
                    <th class="light">{{ $t('Valid until') }}</th>
                    <td>{{ localDate(certificateHealth.certificateDetails.valid_until, 'llll') }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <h2>{{ $t('Certificate chain') }}</h2>

        <div v-if="!certificateHealth" class="oh-flex oh-w-full oh-py-16 oh-justify-center oh-items-center">
            <loader/>
        </div>

        <div v-if="certificateHealth">
            <table class="data">
                <tbody>
                <tr v-for="(issuer, index) in certificateHealth.certificateChainIssuers">
                    <th class="light">#{{ index + 1 }}</th>
                    <td>
                        {{ $t('Issuer') }}: {{ issuer.split(',')[1] }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import Api from "../../helpers/Api";
import {localDateMethod} from "../../helpers/Mixins";

export default {
    name: "CertificateHealth",
    mixins: [localDateMethod],
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
