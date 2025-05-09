<template>
  <LoadingCard :loading="loading" class="px-6 py-4">
    <h3 class="h-6 flex mb-3 text-sm font-bold">
      {{ title }}

      <span class="ml-auto font-semibold text-gray-400 text-xs"
        >({{ formattedTotal }} {{ __('total') }})</span
      >
    </h3>

    <HelpTextTooltip :text="helpText" :width="helpWidth" />

    <div class="flex min-h-[90px]">
      <div
        class="flex-1 overflow-hidden overflow-y-auto"
        :class="{
          'max-h-[90px]': legendsHeight === 'fixed',
        }"
      >
        <ul>
          <li
            v-for="item in formattedItems"
            :key="item.color"
            class="text-xs leading-normal"
          >
            <span
              class="inline-block rounded-full w-2 h-2 mr-2"
              :style="{
                backgroundColor: item.color,
              }"
            />{{ item.label }} ({{ item.value }} - {{ item.percentage }}%)
          </li>
        </ul>
      </div>

      <div
        ref="chart"
        class="flex-none rounded-b-lg ct-chart mr-4 w-[90px] h-[90px]"
        :class="{ invisible: this.currentTotal <= 0 }"
      />
    </div>
  </LoadingCard>
</template>

<script>
import debounce from 'lodash/debounce'
import sumBy from 'lodash/sumBy'
import { PieChart } from 'chartist'
import 'chartist/dist/index.css'

const colorForIndex = index =>
  [
    '#F5573B',
    '#F99037',
    '#F2CB22',
    '#8FC15D',
    '#098F56',
    '#47C1BF',
    '#1693EB',
    '#6474D7',
    '#9C6ADE',
    '#E471DE',
  ][index]

export default {
  name: 'BasePartitionMetric',

  props: {
    loading: Boolean,
    title: String,
    helpText: {},
    helpWidth: {},
    chartData: Array,
    legendsHeight: { type: String, default: 'fixed' },
  },

  data: () => ({
    chartist: null,
    resizeObserver: null,
  }),

  watch: {
    chartData: function (newData, oldData) {
      this.renderChart()
    },
  },

  created() {
    const debouncer = debounce(callback => callback(), Nova.config('debounce'))

    this.resizeObserver = new ResizeObserver(entries => {
      debouncer(() => {
        this.renderChart()
      })
    })
  },

  mounted() {
    let donutWidth = 10

    this.chartist = new PieChart(this.$refs.chart, this.formattedChartData, {
      donut: true,
      donutWidth,
      startAngle: 270,
      showLabel: false,
    })

    this.chartist.on('draw', context => {
      if (context.type === 'slice') {
        context.element.attr({
          style: `stroke-width: ${donutWidth}px; stroke: ${context.meta.color} !important;`,
        })
      }
    })

    this.resizeObserver.observe(this.$refs.chart)
  },

  beforeUnmount() {
    this.resizeObserver.unobserve(this.$refs.chart)
  },

  methods: {
    renderChart() {
      this.chartist.update(this.formattedChartData)
    },

    getItemColor(item, index) {
      return typeof item.color === 'string' ? item.color : colorForIndex(index)
    },
  },

  computed: {
    chartClasses() {
      return []
    },

    formattedChartData() {
      return { labels: this.formattedLabels, series: this.formattedData }
    },

    formattedItems() {
      return this.chartData.map((item, index) => {
        return {
          label: item.label,
          value: Nova.formatNumber(item.value),
          color: this.getItemColor(item, index),
          percentage: Nova.formatNumber(String(item.percentage)),
        }
      })
    },

    formattedLabels() {
      return this.chartData.map(item => item.label)
    },

    formattedData() {
      return this.chartData.map((item, index) => {
        return {
          value: item.value,
          meta: { color: this.getItemColor(item, index) },
        }
      })
    },

    formattedTotal() {
      let total = this.currentTotal.toFixed(2)
      let roundedTotal = Math.round(total)

      if (roundedTotal.toFixed(2) == total) {
        return Nova.formatNumber(new String(roundedTotal))
      }

      return Nova.formatNumber(new String(total))
    },

    currentTotal() {
      return sumBy(this.chartData, 'value')
    },
  },
}
</script>
