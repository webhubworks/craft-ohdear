<template>
    <span :class="`oh-whitespace-nowrap oh-inline-flex oh-items-center ${borderRadius} ${padding} ${fontSize} oh-leading-5 oh-font-medium oh-rounded-sm ${bgColor} ${textColor} hover:oh-no-underline`">
        <svg v-if="!dotless" :class="`oh--ml-0.5 oh-mr-1.5 oh-h-2 oh-w-2 ${dotColor}`" fill="currentColor" viewBox="0 0 8 8">
        <circle cx="4" cy="4" r="3"/>
        </svg>
        {{ $t(label) }}
        <slot></slot>
    </span>
</template>

<script>
    export default {
        name: "Badge",
        computed: {
            borderRadius() {
                return this.round ? 'oh-rounded-xl' : '';
            },
            padding() {
                return this.small ? 'oh-px-1' : 'oh-px-2.5 oh-py-0.5';
            },
            fontSize() {
                return this.small ? 'oh-text-xs' : 'oh-text-sm';
            },
            bgColor() {
                switch (this.color) {
                    case 'green':
                        return 'oh-bg-green-200';
                    case 'blue':
                        return 'oh-bg-blue-200';
                    case 'red':
                        return 'oh-bg-red-200';
                    case 'yellow':
                        return 'oh-bg-yellow-200';
                    case 'orange':
                        return 'oh-bg-orange-200';
                    case 'indigo':
                        return 'oh-bg-indigo-200';
                    default:
                        return 'oh-bg-gray-200';
                }
            },
            textColor() {
                switch (this.color) {
                    case 'green':
                        return 'oh-text-green-800';
                    case 'blue':
                        return 'oh-text-blue-800';
                    case 'red':
                        return 'oh-text-red-800';
                    case 'yellow':
                        return 'oh-text-yellow-800';
                    case 'orange':
                        return 'oh-text-orange-800';
                    case 'indigo':
                        return 'oh-text-indigo-800';
                    default:
                        return 'oh-text-gray-800';
                }
            },
            dotColor() {
                switch (this.color) {
                    case 'green':
                        return 'oh-text-green-400';
                    case 'blue':
                        return 'oh-text-blue-400';
                    case 'red':
                        return 'oh-text-red-400';
                    case 'yellow':
                        return 'oh-text-yellow-400';
                    case 'orange':
                        return 'oh-text-orange-400';
                    case 'indigo':
                        return 'oh-text-indigo-400';
                    default:
                        return 'oh-text-gray-400';
                }
            },
        },
        props: {
            small: {
                type: Boolean,
                default: false
            },
            color: {
                type: String,
                required: true
            },
            label: {
                type: String|Promise,
                required: true
            },
            dotless: {
                type: Boolean,
                default: false
            },
            round: {
                type: Boolean,
                default: false
            },
        }
    }
</script>
