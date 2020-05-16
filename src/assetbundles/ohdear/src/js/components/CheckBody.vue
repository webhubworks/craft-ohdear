<template>
    <div>
        {{body}}
    </div>
</template>

<script>
    import Typo from "../helpers/Typo";
    import Check from "../resources/Check";

    export default {
        name: "CheckBody",
        props: {
            check: {
                type: Check,
                required: true
            }
        },
        computed: {
            body() {
                if (!this.check.enabled) {
                    return Typo.checks.bodyDisabled;
                }
                let body = this.check.latestRunResult === 'succeeded' ?
                    Typo.checks[this.check.type].body.good :
                    Typo.checks[this.check.type].body.bad;
                return body.replace('{:fromNow}', this.check.latestRunEndedAt.fromNow());
            }
        }
    }
</script>

<style scoped>

</style>
