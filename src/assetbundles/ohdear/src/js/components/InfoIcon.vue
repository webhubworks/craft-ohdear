<template>
    <span class="info">
        <slot></slot>
    </span>
</template>

<script>
    export default {
        name: "InfoIcon",
        mounted() {
            new window.Craft.InfoIcon(this.$el);
        }
    }
</script>

<style scoped>

</style>
