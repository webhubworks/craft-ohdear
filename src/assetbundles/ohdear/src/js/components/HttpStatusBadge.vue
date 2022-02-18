<template>
    <badge :color="color" :dotless="true" :title="status" :label="statusLabel"/>
</template>

<script>
    import HttpStatusMap from "../helpers/HttpStatusMap";

    export default {
        name: "HttpStatusBadge",
        props: {
            status: {
                type: [String, Number],
                validator: function (value) {
                    if (valueIsNoResponse(value)) {
                        return true;
                    }
                    const status = parseInt(value);
                    return status > 99 && status < 600;
                }
            }
        },
        computed: {
            color() {
                if (valueIsNoResponse(this.status)) {
                    return 'red';
                }
                const status = parseInt(this.status);
                if (status >= 500) {
                    return 'orange';
                }
                if (status >= 400) {
                    return 'red';
                }
                if (status >= 300) {
                    return 'blue';
                }
                if (status >= 200) {
                    return 'green';
                }
                return 'gray';
            },
            statusLabel() {
                if (valueIsNoResponse(this.status)) {
                    return this.$t('No response');
                }
                if (HttpStatusMap.hasOwnProperty(this.status.toString())) {
                    return `${this.status} ${HttpStatusMap[this.status.toString()]}`;
                }
                return this.$t(this.status);
            }
        }
    }

    function valueIsNoResponse(value) {
        return value.toString().toLowerCase() === 'no response'
    }

</script>

<style scoped>

</style>
