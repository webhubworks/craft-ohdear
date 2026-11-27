<template>
  <div>

    <h2>{{ $t('Check details') }}</h2>

    <div v-if="loadingSite" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 74px;">
      <loader/>
    </div>

    <table v-if="!loadingSite" class="data">
      <tbody>
      <tr>
        <th class="light">{{ $t('Status') }}</th>
        <td>
          <check-badge loader-position="right" :check="check"/>
        </td>
      </tr>
      <tr>
        <th class="light">{{ $t('Last run') }}</th>
        <td class="xs:oh-whitespace-nowrap">{{ lastRun }}</td>
      </tr>
      </tbody>
    </table>

    <div class="oh-my-4 oh-flex oh-space-x-2">
      <h2 class="oh-mb-0">{{ $t('Scores') }}</h2>
      <button @click="showScoresDescription = !showScoresDescription" class="hover:oh-underline">
        <span v-if="!showScoresDescription">{{ $t('Show legend') }}</span>
        <span v-if="showScoresDescription">{{ $t('Hide legend') }}</span>
      </button>
    </div>

    <div v-if="loadingLatestLightHouseReport" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 74px;">
      <loader/>
    </div>

    <lighthouse-scores v-if="!loadingLatestLightHouseReport"
                       :show-description="showScoresDescription"
                       :lighthouse-report="latestLighthouseReport"></lighthouse-scores>

    <div class="oh-my-4 oh-flex oh-space-x-2">
      <h2 class="oh-mb-0">{{ $t('Metrics') }}</h2>
      <button @click="showMetricsDescription = !showMetricsDescription" class="hover:oh-underline">
        <span v-if="!showMetricsDescription">{{ $t('Show descriptions') }}</span>
        <span v-if="showMetricsDescription">{{ $t('Hide descriptions') }}</span>
      </button>
    </div>

    <div v-if="loadingLatestLightHouseReport" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 74px;">
      <loader/>
    </div>

    <lighthouse-metrics v-if="!loadingLatestLightHouseReport"
                        :show-description="showMetricsDescription"
                        :lighthouse-report="latestLighthouseReport"></lighthouse-metrics>
  </div>
</template>

<script>
import Api from "../../helpers/Api";
import {fetchesSite, hasCheck} from "../../helpers/Mixins";
import LighthouseMetrics from "../LighthouseMetrics.vue";
import LighthouseScores from "../LighthouseScores.vue";

export default {
  name: "Lighthouse",
  mixins: [fetchesSite, hasCheck],
  data() {
    return {
      showMetricsDescription: false,
      showScoresDescription: false,
      checkType: 'lighthouse',
      latestLighthouseReport: null,
      loadingLatestLightHouseReport: true,
    }
  },
  components: {
    LighthouseMetrics,
    LighthouseScores,
  },
  mounted() {
    this.fetchLatestLighthouseReport();
  },
  methods: {
    fetchLatestLighthouseReport() {
      return Api.getLatestLighthouseReport().then(response => {
        this.latestLighthouseReport = response.data.latestLighthouseReport;
        this.loadingLatestLightHouseReport = false;
      })
    },
  }
}
</script>
