<template>
  <BaseTrendMetric
    @selected="handleRangeSelected"
    :title="card.name"
    :help-text="card.helpText"
    :help-width="card.helpWidth"
    :value="value"
    :chart-data="data"
    :ranges="card.ranges"
    :format="format"
    :prefix="prefix"
    :suffix="suffix"
    :suffix-inflection="suffixInflection"
    :selected-range-key="selectedRangeKey"
    :loading="loading"
  />
</template>

<script>
import { InteractsWithDates, MetricBehavior } from '@/mixins'

export default {
  name: 'TrendMetric',

  mixins: [InteractsWithDates, MetricBehavior],

  data: () => ({
    loading: true,
    value: '',
    data: [],
    format: '(0[.]00a)',
    prefix: '',
    suffix: '',
    suffixInflection: true,
    selectedRangeKey: null,
  }),

  watch: {
    resourceId() {
      this.fetch()
    },
  },

  created() {
    if (this.hasRanges) {
      this.selectedRangeKey =
        this.card.selectedRangeKey || this.card.ranges[0].value
    }

    this.fetch()
  },

  mounted() {
    if (this.card && this.card.refreshWhenFiltersChange === true) {
      Nova.$on('filter-changed', this.fetch)
      Nova.$on('filter-reset', this.fetch)
    }
  },

  beforeUnmount() {
    if (this.card && this.card.refreshWhenFiltersChange === true) {
      Nova.$off('filter-changed', this.fetch)
      Nova.$off('filter-reset', this.fetch)
    }
  },

  methods: {
    handleRangeSelected(key) {
      this.selectedRangeKey = key
      this.fetch()
    },

    handleFetchCallback() {
      return ({
        data: {
          value: {
            labels,
            trend,
            value,
            prefix,
            suffix,
            suffixInflection,
            format,
          },
        },
      }) => {
        this.value = value
        this.labels = Object.keys(trend)
        this.data = {
          labels: Object.keys(trend),
          series: [
            Object.entries(trend).map(([key, value]) => ({
              meta: `${key}`,
              value,
            })),
          ],
        }
        this.format = format || this.format
        this.prefix = prefix || this.prefix
        this.suffix = suffix || this.suffix
        this.suffixInflection = suffixInflection
        this.loading = false
      }
    },
  },

  computed: {
    hasRanges() {
      return this.card.ranges.length > 0
    },

    metricPayload() {
      const payload = {
        params: {
          timezone: this.userTimezone,
          twelveHourTime: this.usesTwelveHourTime,
        },
      }

      if (
        !Nova.missingResource(this.resourceName) &&
        this.card &&
        this.card.refreshWhenFiltersChange === true
      ) {
        payload.params.filter =
          this.$store.getters[`${this.resourceName}/currentEncodedFilters`]
      }

      if (this.hasRanges) {
        payload.params.range = this.selectedRangeKey
      }

      return payload
    },
  },
}
</script>
