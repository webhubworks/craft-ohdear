<template>
    <div :style="rootStyle">

        <h2>{{ $t('Check details') }}</h2>

        <div v-if="loadingSite" class="oh-w-full oh-justify-center oh-items-center oh-flex" style="height: 109px;">
            <loader></loader>
        </div>

        <table class="data collapsible" v-if="!loadingSite">
            <tbody>
            <tr>
                <th class="light">{{ $t('Status') }}</th>
                <td>
                    <check-badge :check="check"/>
                </td>
            </tr>
            <tr>
                <th class="light">{{ $t('Last run') }}</th>
                <td>{{ lastRun }}</td>
            </tr>
            <tr>
                <th class="light">{{ $t('Last downtime') }}</th>
                <td>{{ lastDowntime }}</td>
            </tr>
            </tbody>
        </table>

        <h2>{{ $t('History') }}</h2>

        <div class="oh-relative">
            <div v-if="heatMapLoading" class="oh-flex oh-absolute oh-left-0 oh-top-0 oh-w-full oh-justify-center oh-items-center" :style="loaderStyle">
                <loader/>
            </div>

            <div class="oh-flex" :class="{'oh-opacity-0': heatMapLoading}">

                <div class="oh-flex oh-flex-col oh-flex-wrap oh-overflow-x-auto" :style="weekdayLegendStyle">
                    <div v-for="label in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']"
                         class="oh-h-8 oh-w-8 oh-m-0.5 oh-font-medium oh-flex oh-justify-center oh-items-center light">
                        <span :title="$t(label)">{{$t(label).substr(0,2)}}</span>
                    </div>
                </div>

                <div class="oh-w-full oh-overflow-x-auto" ref="heatmap">

                    <div class="oh-relative" :style="monthLegendStyle">
                    <span v-for="month in legendMonths" v-if="month.show" class="oh-block oh-absolute oh-top-0 light oh-font-medium" :style="getMonthStyle(month)">
                        {{ $t(month.label) }}
                    </span>
                    </div>

                    <div class="oh-flex oh-flex-col oh-flex-wrap" :class="{'show-percentage': showPercentage}" :style="heatMapStyle">

                        <div v-for="day in uptime"
                             v-tooltip.left="tooltipContent"
                             @mouseover="setTooltipContent(day)"
                             :style="getCellStyle(day)"
                             class="oh-h-8 oh-w-8 oh-m-0.5 oh-rounded-sm oh-flex oh-justify-center oh-items-center cell">
                            <span class="oh-whitespace-no-wrap oh-text-xs oh-font-medium pointer-events-none" style="color: rgba(0,0,0,0.45)">{{day.uptimePercentage}}</span>
                        </div>

                        <div v-for="day in daysToGoThisWeek" class="oh-bg-gray-300 oh-h-8 oh-w-8 oh-m-0.5 oh-rounded-sm"></div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    import Api from "../../helpers/Api";
    import DayJs from 'dayjs';
    import prettyTime from "../../helpers/PrettyTime";
    import {sortBy} from 'lodash';
    import {fetchesDowntime, fetchesSite, hasCheck} from "../../helpers/Mixins";
    import LocalDate from '../../helpers/LocalDate';

    const CELL_SIZE = 32;
    const CELL_MARGIN = 2;
    const LEGEND_HEIGHT = 22;
    const SCROLLBAR_HEIGHT = 8;
    const COLOR_MAP = {
        100: '#68d391',
        90: '#c6e0a4',
        80: '#c6e0a4',
        70: '#c6e0a4',
        60: '#FFD97D',
        50: '#FFD97D',
        40: '#FFD97D',
        30: '#f79d69',
        20: '#f79d69',
        10: '#f79d69',
        0: '#EE6055',
    };

    export default {
        name: "Uptime",
        mixins: [fetchesSite, fetchesDowntime, hasCheck],
        data() {
            return {
                checkType: 'uptime',
                periodStart: this.getMondayAYearAgo().format('YYYYMMDDHHmmss'),
                periodEnd: DayJs().format('YYYYMMDDHHmmss'),
                uptime: [],
                downtime: [],
                heatMapLoading: true,
                showPercentage: false,
                tooltipContent: ''
            }
        },
        computed: {
            lastDowntime() {
                if (!this.downtime.length) {
                    return "The site has never been down. Nice work!";
                }
                const lastDowntime = LocalDate(this.downtime[0].endedAt);
                return `${lastDowntime.fromNow()} on ${lastDowntime.format('llll')}`;
            },
            daysToGoThisWeek() {
                return Math.abs(7 - this.uptime.length % 7);
            },
            rootStyle() {
                return `--scrollbar-height: ${SCROLLBAR_HEIGHT}px;`;
            },
            monthLegendStyle() {
                return `height: ${LEGEND_HEIGHT}px;`;
            },
            weekdayLegendStyle() {
                // height of a week (7 cells)
                return `height: ${7 * (CELL_SIZE + 2 * CELL_MARGIN)}px; margin-top: ${LEGEND_HEIGHT}px;`;
            },
            heatMapStyle() {
                return {
                    // height of a week (7 cells)
                    'height': `${7 * (CELL_SIZE + 2 * CELL_MARGIN)}px`,
                    // width of weeks in the period
                    'max-width': `${(CELL_SIZE + 2 * CELL_MARGIN) * Math.ceil(this.uptime.length / 7)}px`
                };
            },
            loaderStyle() {
                // height of a week (7 cells) + scrollbar
                return `height: ${7 * (CELL_SIZE + 2 * CELL_MARGIN) + SCROLLBAR_HEIGHT}px;`;
            },
            legendMonths() {
                if (!this.uptime.length) {
                    return [];
                }
                let firstDate = LocalDate(this.uptime[0].datetime);
                let months = [{
                    date: firstDate,
                    show: true,
                    label: firstDate.month() === 0 ? firstDate.year() : firstDate.format('MMMM'),
                    offset: 0
                }];
                for (let i = 0; i < this.uptime.length; i++) {
                    let currentCellMonth = this.uptime[i].datetime.substr(0, 7);
                    if (i + 1 < this.uptime.length) {
                        let nextCellMonth = this.uptime[i + 1].datetime.substr(0, 7);
                        if (currentCellMonth !== nextCellMonth) {
                            let date = DayJs(this.uptime[i + 1].datetime);
                            months.push({
                                date: date,
                                show: true,
                                label: date.month() === 0 ? date.year() : date.format('MMMM'),
                                offset: i + 1
                            });
                            // if the second month is not at least 2 columns from left
                            // make the first month invisible because there will be not enough space
                            if ((i + 1) < 15) {
                                months[0].show = false;
                            }
                        }
                    }
                }
                return months;
            },
        },
        mounted() {
            this.registerKeyboardEventHandlers();
            this.fetchUptime(
                this.periodStart,
                this.periodEnd,
                'day')
                .then(uptime => {
                    this.uptime = uptime;
                    // this weird thingy makes sure that we wait for render,
                    // (~ 10 frames on 60fps) then scroll to the end of the heat map
                    // and finally show it
                    setTimeout(() => {
                        this.$refs.heatmap.scrollTo(this.$refs.heatmap.scrollWidth, 0);
                        this.heatMapLoading = false;
                    }, 167);
                });
        },
        methods: {
            setTooltipContent(day) {
                const date = day.datetime.substr(0, 10);
                if (!this.downtimeGroupedByDay.hasOwnProperty(date)) {
                    this.tooltipContent = `${LocalDate(date).format('L')}`;
                    return;
                }

                let content = `
                    <p>${LocalDate(date).format('L')}</p>
                    <p>Site was down</p>
                `;

                let downtimes = this.downtimeGroupedByDay[date].map(downtime => {
                    const startedAt = LocalDate(downtime.startedAt);
                    const endedAt = LocalDate(downtime.endedAt);
                    return {
                        'startedAt': startedAt,
                        'endedAt': endedAt,
                        'diff': endedAt.diff(startedAt)
                    }
                });

                downtimes = sortBy(downtimes, 'startedAt');

                downtimes.forEach(downtime => {
                    content += `<p>at ${downtime.startedAt.format('LT')} for ${prettyTime(downtime.diff)}</p>`;
                });

                const total = downtimes.map(dt => dt.diff).reduce((sum, diff) => sum + diff);
                content += `<p>Total: ${prettyTime(total)}</p>`

                this.tooltipContent = `<div>${content}</div>`;
            },
            getMonthStyle(month) {
                return `left: ${2 + (parseInt(month.offset / 7) * 36)}px`;
            },
            getCellStyle(day) {
                let isToday = LocalDate(day.datetime).isToday();
                return {
                    'background-color': COLOR_MAP[Math.floor(day.uptimePercentage / 10) * 10],
                    'width': `${CELL_SIZE}px`,
                    'height': `${CELL_SIZE}px`,
                    'margin': `${CELL_MARGIN}px`,
                    'animation': isToday ? 'ohdear-pulse 2s infinite' : ''
                };
            },
            fetchUptime(startedAt, endedAt, period) {
                return Api.getUptime(startedAt, endedAt, period)
                    .then(response => {
                        return response.data.uptime;
                    })
            },
            registerKeyboardEventHandlers() {
                document.addEventListener('keydown', event => {
                    if (event.code === 'AltLeft') {
                        this.showPercentage = true;
                    }
                });
                document.addEventListener('keyup', event => {
                    if (event.code === 'AltLeft') {
                        this.showPercentage = false;
                    }
                });
            }
        },
    }
</script>

<style scoped>
    /* width */
    ::-webkit-scrollbar {
        height: var(--scrollbar-height);
    }

    /* Track */
    ::-webkit-scrollbar-track {
        background: #f8f8f8;
    }

    /* Handle */
    ::-webkit-scrollbar-thumb {
        background: #ccc;
    }

    /* Handle on hover */
    ::-webkit-scrollbar-thumb:hover {
        background: #888;
    }

    .cell * {
        display: none;
    }

    .cell:hover *,
    .show-percentage .cell * {
        display: block;
    }

    .cell:hover {
        cursor: help;
    }
</style>
