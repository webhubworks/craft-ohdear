<template>
    <badge :color="color" :label="label" :dotless="false"/>
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
            }
        },
        computed: {
            label() {
                return this.check.latestRunResult === 'succeeded' ?
                    Typo.checks[this.check.type].badge.good :
                    Typo.checks[this.check.type].badge.bad;
            },
            color() {
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
                    default:
                        return 'gray'
                }
            }
        }
    }
</script>

<style scoped>

</style>
