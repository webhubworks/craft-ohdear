<template>
    <div>
        <div class="oh-flex oh-justify-end">
            <div id="timespan-field" class="field first oh-flex oh-items-center">
                <div class="heading oh-mr-2">
                    <label id="timespan-label" for="timespan">{{ $t('Range') }}</label>
                </div>
                <div class="input ltr">
                    <div class="flex">
                        <div>
                            <div class="select">
                                <select id="timespan" name="timespan" @change="update($event.target.value)">
                                    <option value="last-hour">{{ $t('Last hour') }}</option>
                                    <option value="last-24-hours">{{ $t('Last 24 hours') }}</option>
                                    <option value="last-week">{{ $t('Last week') }}</option>
                                    <option value="last-month">{{ $t('Last month') }}</option>
                                    <option value="last-year">{{ $t('Last year') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex-grow">
                            <input type="text" id="tempSubpath" class="ltr hidden text fullwidth" name="tempSubpath"
                                   autocomplete="off" placeholder="Pfad/zum/Unterverzeichnis" dir="ltr">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <loader v-show="loading"></loader>

        <canvas id="performanceChart"></canvas>
    </div>
</template>

<script>
import Api from '../helpers/Api';
import DayJs from "dayjs";

export default {
    name: "PerformanceChart",
    data() {
        return {
            start: null,
            end: DayJs().utc(),
            groupBy: '1m',
            loading: true,
        }
    },
    mounted() {
        this.update('last-hour');
    },
    methods: {
        getLabels(data) {
            return _.map(data, 'createdAt');
        },
        getDataColumn(data, columnName) {
            return _.map(data, columnName);
        },
        update(timespan) {
            this.set(timespan);
            this.fetchPerformance(this.start, this.end, this.groupBy);
        },
        fetchPerformance(start, end, groupBy = null) {
            this.loading = true;
            return Api.getPerformance(start.format('YYYY-MM-DD HH:mm:ss'), end.format('YYYY-MM-DD HH:mm:ss'), groupBy).then(response => {
                this.initChart(Object.values(response.data.performance));
                this.loading = false;
            })
        },
        set(timespan) {
            switch (timespan) {
                case 'last-hour':
                    this.start = DayJs().utc().subtract(1, 'hour')
                    this.groupBy = 'minute';
                    break;
                case 'last-24-hours':
                    this.start = DayJs().utc().subtract(24, 'hour')
                    this.groupBy = 'hour';
                    break;
                case 'last-week':
                    this.start = DayJs().utc().subtract(7, 'day')
                    this.groupBy = 'hour';
                    break;
                case 'last-month':
                    this.start = DayJs().utc().subtract(1, 'month')
                    this.groupBy = 'day';
                    break;
                case 'last-year':
                    this.start = DayJs().utc().subtract(1, 'year')
                    this.groupBy = 'day';
                    break;
                default:
            }
        },
        destroyChart() {
            const chart = Chart.getChart('performanceChart');
            if (chart) {
                chart.destroy();
            }
        },
        initChart(data) {
            this.destroyChart();
            data = data.filter(entry => entry !== null);
            const ctx = document.getElementById('performanceChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.getLabels(data),
                    datasets: [
                        {
                            label: 'DNS Lookup time',
                            borderColor: "#ffa1b5",
                            borderWidth: 1,
                            backgroundColor: "rgb(255, 224, 230, 0.6)",
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'dnsTimeInSeconds'),
                        },
                        {
                            label: 'TCP Connection time',
                            borderColor: '#77cfcf',
                            borderWidth: 1,
                            backgroundColor: 'rgb(219, 242, 242, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'tcpTimeInSeconds'),
                        },
                        {
                            label: 'SSL Handshake',
                            borderColor: '#c6aef5',
                            borderWidth: 1,
                            backgroundColor: 'rgb(235, 224, 255, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'sslHandshakeTimeInSeconds'),
                        },
                        {
                            label: 'Craft server processing',
                            borderColor: '#86c7f3',
                            borderWidth: 1,
                            backgroundColor: 'rgb(55, 171, 255, 0.26)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'remoteServerProcessingTimeInSeconds'),
                        },
                        {
                            label: 'Content download',
                            borderColor: '#ffd980',
                            borderWidth: 1,
                            backgroundColor: 'rgb(255, 245, 221, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'downloadTimeInSeconds'),
                        },
                    ]
                },
                options: {
                    title: {
                        display: false,
                    },
                    elements: {
                        line: {
                            tension: 0.3,
                            fill: true,
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            mode: 'x',
                            intersect: false,
                            position: 'nearest',
                            callbacks: {
                                label: function (context) {
                                    let label = context.chart.data.datasets[context.datasetIndex].label || '';

                                    if (label) {
                                        label += ': ';
                                    }

                                    // Calculate the total time spent for each request
                                    let total = 0;

                                    for (let i = 0; i < context.chart.data.datasets.length; i++) {
                                        total += Math.round(context.chart.data.datasets[i].data[context.dataIndex] * 100000) / 100; // Force as float
                                    }

                                    label += (Math.round(context.parsed.y * 100000) / 100) + 'ms';

                                    if (context.datasetIndex !== context.chart.data.datasets.length - 1) {
                                        return label;
                                    } else {
                                        return [label, 'Total : ' + (Math.round(total * 100) / 100) + 'ms'];
                                    }
                                }
                            }
                        },
                    },
                    responsive: true,
                    aspectRatio: 2,
                    maintainAspectRatio: true,
                    scales: {
                        xAxes: {
                            stacked: true,
                            ticks: {
                                major: {
                                    enabled: true,
                                    fontStyle: 'bold'
                                },
                                source: 'data',
                                autoSkip: true,
                                autoSkipPadding: 50,
                                maxRotation: 60,
                                minRotation: 50,
                            },
                        },
                        yAxes: {
                            stacked: true,
                            ticks: {
                                callback: function (value) {
                                    return value * 1000 + 'ms';
                                }
                            }
                        }
                    },

                    legend: {
                        position: 'bottom',
                    }
                }
            });
        }
    },
}
</script>
