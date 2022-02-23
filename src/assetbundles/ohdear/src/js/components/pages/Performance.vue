<template>
    <div>

        <h2>{{ $t('Check details') }}</h2>

        <table class="data collapsible">
            <tbody>
            <tr>
                <th class="light">{{ $t('Status') }}</th>
                <td>
                    <check-badge loader-position="right" :check="check" />
                </td>
            </tr>
            <tr>
                <th class="light">{{ $t('Last run') }}</th>
                <td>{{ lastRun }}</td>
            </tr>
            </tbody>
        </table>

        <h2>{{ $t('History') }}</h2>

        <performance-chart></performance-chart>
    </div>
</template>

<script>
import {fetchesSite, hasCheck} from "../../helpers/Mixins";

export default {
    name: "Performance",
    mixins: [fetchesSite, hasCheck],
    data() {
        return {
            checkType: 'performance',
        }
    }
}
</script>
