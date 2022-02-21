<template>
    <div>
        <div>
            <label for="timespan" class="oh-block oh-text-sm oh-font-medium oh-text-gray-700">Location</label>
            <select id="timespan" @change="setTimespan($event.target.value)" name="location" class="oh-mt-1 oh-block oh-w-full oh-pl-3 oh-pr-10 oh-py-2 oh-text-base oh-border-gray-300 oh-focus:outline-none oh-focus:ring-indigo-500 oh-focus:border-indigo-500 oh-sm:text-sm oh-rounded-md">
                <option value="last-hour">{{ $t('Last hour') }}</option>
                <option value="last-24-hours">{{ $t('Last 24 hours') }}</option>
            </select>
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
            end: DayJs().utc().format('YYYYMMDDHHmmss'),
            loading: true,
        }
    },
    methods: {
        getLabels(data) {
            const delta = Math.floor(data.length / 14);
            let labels = [];
            for (let i = 0; i < data.length; i = i + delta) {
                labels.push(data[i].created_at);
            }
            return labels;
        },
        getDataColumn(data, columnName) {
            return _.map(data, columnName);
        },
        fetchPerformance(start, end, timeframe = null) {
            this.loading = true;
            return Api.getPerformance(start, end, timeframe).then(response => {
                this.initChart(Object.values(response.data.performance.data));
                this.loading = false;
            })
        },
        setTimespan(timespan) {
            switch (timespan) {
                case 'last-hour':
                    this.start = DayJs().utc().subtract(1, 'hour').format('YYYYMMDDHHmmss')
                    break;
                case 'last-24-hours':
                    this.start = DayJs().utc().subtract(24, 'hour').format('YYYYMMDDHHmmss')
                    break;
                default:
            }
        },
        initChart(data) {
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
                            data: this.getDataColumn(data, 'dns_time_in_seconds'),
                        },
                        {
                            label: 'TCP Connection time',
                            borderColor: '#77cfcf',
                            borderWidth: 1,
                            backgroundColor: 'rgb(219, 242, 242, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'tcp_time_in_seconds'),
                        },
                        {
                            label: 'SSL Handshake',
                            borderColor: '#c6aef5',
                            borderWidth: 1,
                            backgroundColor: 'rgb(235, 224, 255, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'ssl_handshake_time_in_seconds'),
                        },
                        {
                            label: 'Craft server processing',
                            borderColor: '#86c7f3',
                            borderWidth: 1,
                            backgroundColor: 'rgb(55, 171, 255, 0.26)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'remote_server_processing_time_in_seconds'),
                        },
                        {
                            label: 'Content download',
                            borderColor: '#ffd980',
                            borderWidth: 1,
                            backgroundColor: 'rgb(255, 245, 221, 0.6)',
                            categoryPercentage: 1,
                            barPercentage: 1,
                            pointRadius: 0,
                            data: this.getDataColumn(data, 'download_time_in_seconds'),
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
    mounted() {
        this.setTimespan('last-hour');
        this.fetchPerformance(this.start, this.end);
    }
}
</script>
