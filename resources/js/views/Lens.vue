<template>
  <LoadingView :loading="initialLoading" :dusk="lens + '-lens-component'">
    <Head :title="lensName" />

    <Cards
      v-if="shouldShowCards"
      :cards="cards"
      :resource-name="resourceName"
      :lens="lens"
    />

    <Heading
      v-if="resourceResponse"
      class="mb-3"
      :class="{ 'mt-6': shouldShowCards }"
      v-text="lensName"
      dusk="lens-heading"
    />

    <div
      v-if="searchable || availableStandaloneActions.length > 0"
      class="flex items-center mb-6"
    >
      <IndexSearchInput
        v-if="searchable"
        :searchable="searchable"
        v-model="search"
      />

      <!-- Action Dropdown -->
      <ActionDropdown
        v-if="availableStandaloneActions.length > 0"
        @actionExecuted="() => fetchPolicies()"
        class="ml-auto"
        :resource-name="resourceName"
        :via-resource="''"
        :via-resource-id="''"
        :via-relationship="''"
        :relationship-type="''"
        :actions="availableStandaloneActions"
        :selected-resources="selectedResourcesForActionSelector"
        :endpoint="lensActionEndpoint"
      />
    </div>

    <Card>
      <ResourceTableToolbar
        :actions-endpoint="lensActionEndpoint"
        :action-query-string="actionQueryString"
        :all-matching-resource-count="allMatchingResourceCount"
        :authorized-to-delete-any-resources="authorizedToDeleteAnyResources"
        :authorized-to-delete-selected-resources="
          authorizedToDeleteSelectedResources
        "
        :authorized-to-force-delete-any-resources="
          authorizedToForceDeleteAnyResources
        "
        :authorized-to-force-delete-selected-resources="
          authorizedToForceDeleteSelectedResources
        "
        :authorized-to-restore-any-resources="authorizedToRestoreAnyResources"
        :authorized-to-restore-selected-resources="
          authorizedToRestoreSelectedResources
        "
        :available-actions="availableActions"
        :clear-selected-filters="clearSelectedFilters"
        :close-delete-modal="closeDeleteModal"
        :currently-polling="currentlyPolling"
        :delete-all-matching-resources="deleteAllMatchingResources"
        :delete-selected-resources="deleteSelectedResources"
        :filter-changed="filterChanged"
        :force-delete-all-matching-resources="forceDeleteAllMatchingResources"
        :force-delete-selected-resources="forceDeleteSelectedResources"
        :get-resources="getResources"
        :has-filters="hasFilters"
        :have-standalone-actions="haveStandaloneActions"
        :lens="lens"
        :is-lens-view="isLensView"
        :per-page-options="perPageOptions"
        :per-page="perPage"
        :pivot-actions="pivotActions"
        :pivot-name="pivotName"
        :resources="resources"
        :resource-information="resourceInformation"
        :resource-name="resourceName"
        :restore-all-matching-resources="restoreAllMatchingResources"
        :restore-selected-resources="restoreSelectedResources"
        :current-page-count="resources.length"
        :select-all-checked="selectAllChecked"
        :select-all-matching-checked="selectAllMatchingResources"
        @deselect="deselectAllResources"
        :selected-resources="selectedResources"
        :selected-resources-for-action-selector="
          selectedResourcesForActionSelector
        "
        :should-show-action-selector="shouldShowActionSelector"
        :should-show-checkboxes="shouldShowSelectAllCheckboxes"
        :should-show-delete-menu="shouldShowDeleteMenu"
        :should-show-polling-toggle="shouldShowPollingToggle"
        :soft-deletes="softDeletes"
        @start-polling="startPolling"
        @stop-polling="stopPolling"
        :toggle-select-all-matching="toggleSelectAllMatching"
        :toggle-select-all="toggleSelectAll"
        :toggle-polling="togglePolling"
        :trashed-changed="trashedChanged"
        :trashed-parameter="trashedParameter"
        :trashed="trashed"
        :update-per-page-changed="updatePerPageChanged"
        :via-many-to-many="viaManyToMany"
        :via-resource="viaResource"
      />

      <LoadingView
        :loading="loading"
        :variant="!resourceResponse ? 'default' : 'overlay'"
      >
        <IndexErrorDialog
          v-if="resourceResponseError != null"
          :resource="resourceInformation"
          @click="getResources"
        />

        <template v-else>
          <IndexEmptyDialog
            v-if="!resources.length"
            :create-button-label="createButtonLabel"
            :singular-name="singularName"
            :resource-name="resourceName"
            :via-resource="viaResource"
            :via-resource-id="viaResourceId"
            :via-relationship="viaRelationship"
            :relationship-type="relationshipType"
            :authorized-to-create="authorizedToCreate"
            :authorized-to-relate="authorizedToRelate"
          />

          <ResourceTable
            :authorized-to-relate="authorizedToRelate"
            :resource-name="resourceName"
            :resources="resources"
            :singular-name="singularName"
            :selected-resources="selectedResources"
            :selected-resource-ids="selectedResourceIds"
            :actions-are-available="actionsAreAvailable"
            :actions-endpoint="lensActionEndpoint"
            :should-show-checkboxes="shouldShowCheckboxes"
            :via-resource="viaResource"
            :via-resource-id="viaResourceId"
            :via-relationship="viaRelationship"
            :relationship-type="relationshipType"
            :update-selection-status="updateSelectionStatus"
            :sortable="true"
            @order="orderByField"
            @reset-order-by="resetOrderBy"
            @delete="deleteResources"
            @restore="restoreResources"
            @actionExecuted="getResources"
            ref="resourceTable"
          />

          <ResourcePagination
            :pagination-component="paginationComponent"
            :should-show-pagination="shouldShowPagination"
            :has-next-page="hasNextPage"
            :has-previous-page="hasPreviousPage"
            :load-more="loadMore"
            :select-page="selectPage"
            :total-pages="totalPages"
            :current-page="currentPage"
            :per-page="perPage"
            :resource-count-label="resourceCountLabel"
            :current-resource-count="currentResourceCount"
            :all-matching-resource-count="allMatchingResourceCount"
          />
        </template>
      </LoadingView>
    </Card>
  </LoadingView>
</template>

<script>
import {
  HasCards,
  Paginatable,
  PerPageable,
  Deletable,
  IndexConcerns,
  InteractsWithQueryString,
  InteractsWithResourceInformation,
  SupportsPolling,
} from '@/mixins'
import { CancelToken, isCancel } from 'axios'
import { minimum } from '@/util'
import { mapActions } from 'vuex'

export default {
  mixins: [
    HasCards,
    Deletable,
    Paginatable,
    PerPageable,
    IndexConcerns,
    InteractsWithResourceInformation,
    InteractsWithQueryString,
    SupportsPolling,
  ],

  name: 'Lens',

  props: {
    lens: {
      type: String,
      required: true,
    },

    searchable: {
      type: Boolean,
      required: true,
    },
  },

  data: () => ({
    actionCanceller: null,
    resourceHasId: false,
  }),

  /**
   * Mount the component and retrieve its initial data.
   */
  async created() {
    if (!this.resourceInformation) {
      return
    }

    this.getActions()

    Nova.$on('refresh-resources', this.getResources)
  },

  beforeUnmount() {
    Nova.$off('refresh-resources', this.getResources)

    if (this.actionCanceller !== null) this.actionCanceller()
  },

  methods: {
    ...mapActions(['fetchPolicies']),

    /**
     * Get the resources based on the current page, search, filters, etc.
     */
    getResources() {
      this.loading = true
      this.resourceResponseError = null

      this.$nextTick(() => {
        this.clearResourceSelections()

        return minimum(
          Nova.request().get(
            '/nova-api/' + this.resourceName + '/lens/' + this.lens,
            {
              params: this.resourceRequestQueryString,
              cancelToken: new CancelToken(canceller => {
                this.canceller = canceller
              }),
            }
          ),
          300
        )
          .then(({ data }) => {
            this.resources = []

            this.resourceResponse = data
            this.resources = data.resources
            this.softDeletes = data.softDeletes
            this.perPage = data.per_page
            this.resourceHasId = Boolean(data.hasId)

            this.handleResourcesLoaded()
          })
          .catch(e => {
            if (isCancel(e)) {
              return
            }

            this.loading = false
            this.resourceResponseError = e

            throw e
          })
      })
    },

    /**
     * Get the actions available for the current resource.
     */
    getActions() {
      if (this.actionCanceller !== null) this.actionCanceller()

      this.actions = []
      this.pivotActions = null

      Nova.request()
        .get(`/nova-api/${this.resourceName}/lens/${this.lens}/actions`, {
          params: {
            viaResource: this.viaResource,
            viaResourceId: this.viaResourceId,
            viaRelationship: this.viaRelationship,
            relationshipType: this.relationshipType,
            display: 'index',
            resources: this.selectAllMatchingChecked
              ? 'all'
              : this.selectedResourceIds,
          },
          cancelToken: new CancelToken(canceller => {
            this.actionCanceller = canceller
          }),
        })
        .then(response => {
          this.actions = response.data.actions
          this.pivotActions = response.data.pivotActions
          this.resourceHasSoleActions = response.data.counts.sole > 0
          this.resourceHasActions = response.data.counts.resource > 0
        })
        .catch(e => {
          if (isCancel(e)) {
            return
          }

          throw e
        })
    },

    /**
     * Get the count of all of the matching resources.
     */
    getAllMatchingResourceCount() {
      Nova.request()
        .get(
          '/nova-api/' + this.resourceName + '/lens/' + this.lens + '/count',
          {
            params: this.resourceRequestQueryString,
          }
        )
        .then(response => {
          this.allMatchingResourceCount = response.data.count
        })
    },

    /**
     * Load more resources.
     */
    loadMore() {
      if (this.currentPageLoadMore === null) {
        this.currentPageLoadMore = this.currentPage
      }

      this.currentPageLoadMore = this.currentPageLoadMore + 1

      return minimum(
        Nova.request().get(
          '/nova-api/' + this.resourceName + '/lens/' + this.lens,
          {
            params: {
              ...this.resourceRequestQueryString,
              page: this.currentPageLoadMore, // We do this to override whatever page number is in the URL
            },
          }
        ),
        300
      ).then(({ data }) => {
        this.resourceResponse = data
        this.resources = [...this.resources, ...data.resources]

        this.getAllMatchingResourceCount()

        Nova.$emit('resources-loaded', {
          resourceName: this.resourceName,
          lens: this.lens,
          mode: 'lens',
        })
      })
    },
  },

  computed: {
    actionQueryString() {
      return {
        currentSearch: this.currentSearch,
        encodedFilters: this.encodedFilters,
        currentTrashed: this.currentTrashed,
        viaResource: this.viaResource,
        viaResourceId: this.viaResourceId,
        viaRelationship: this.viaRelationship,
      }
    },

    actionsAreAvailable() {
      return this.allActions.length > 0 && this.resourceHasId
    },

    /**
     * Get the endpoint for this resource's actions.
     */
    lensActionEndpoint() {
      return `/nova-api/${this.resourceName}/lens/${this.lens}/action`
    },

    /**
     * Get the endpoint for this resource's metrics.
     */
    cardsEndpoint() {
      return `/nova-api/${this.resourceName}/lens/${this.lens}/cards`
    },

    /**
     * Determine whether the user is authorized to perform actions on the delete menu
     */
    canShowDeleteMenu() {
      return (
        this.resourceHasId &&
        Boolean(
          this.authorizedToDeleteSelectedResources ||
            this.authorizedToForceDeleteSelectedResources ||
            this.authorizedToDeleteAnyResources ||
            this.authorizedToForceDeleteAnyResources ||
            this.authorizedToRestoreSelectedResources ||
            this.authorizedToRestoreAnyResources
        )
      )
    },

    /**
     * The Lens name.
     */
    lensName() {
      if (this.resourceResponse) {
        return this.resourceResponse.name
      }
    },
  },
}
</script>
