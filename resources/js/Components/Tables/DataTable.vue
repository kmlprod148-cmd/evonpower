<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <!-- En-tête avec recherche et actions -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex-1 max-w-sm">
          <label for="search" class="sr-only">Rechercher</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :placeholder="searchPlaceholder"
              @input="handleSearch"
            />
          </div>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Filtres -->
          <slot name="filters" />
          
          <!-- Actions -->
          <slot name="actions" />
        </div>
      </div>
    </div>

    <!-- Tableau -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100',
                column.sortable ? 'cursor-pointer' : 'cursor-default'
              ]"
              @click="column.sortable ? handleSort(column.key) : null"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <span v-if="column.sortable" class="flex flex-col">
                  <ChevronUpIcon
                    :class="[
                      'h-3 w-3',
                      sortBy === column.key && sortOrder === 'asc'
                        ? 'text-blue-500'
                        : 'text-gray-400'
                    ]"
                  />
                  <ChevronDownIcon
                    :class="[
                      'h-3 w-3 -mt-1',
                      sortBy === column.key && sortOrder === 'desc'
                        ? 'text-blue-500'
                        : 'text-gray-400'
                    ]"
                  />
                </span>
              </div>
            </th>
            <th v-if="showActions" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" class="animate-pulse">
            <td :colspan="columns.length + (showActions ? 1 : 0)" class="px-6 py-4">
              <div class="flex items-center space-x-4">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              </div>
            </td>
          </tr>
          
          <tr v-else-if="items.length === 0" class="hover:bg-gray-50">
            <td :colspan="columns.length + (showActions ? 1 : 0)" class="px-6 py-4 text-center text-gray-500">
              {{ emptyMessage }}
            </td>
          </tr>
          
          <tr
            v-else
            v-for="item in items"
            :key="item.id"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
            >
              <slot :name="column.key" :item="item" :value="item[column.key]">
                <span v-if="column.format" v-html="column.format(item[column.key], item)" />
                <span v-else>{{ item[column.key] }}</span>
              </slot>
            </td>
            
            <td v-if="showActions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <slot name="actions" :item="item">
                <div class="flex items-center justify-end space-x-2">
                  <Link
                    v-if="showView"
                    :href="getViewUrl(item)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Voir
                  </Link>
                  <Link
                    v-if="showEdit"
                    :href="getEditUrl(item)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Modifier
                  </Link>
                  <button
                    v-if="showDelete"
                    @click="handleDelete(item)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Supprimer
                  </button>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
      <div class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <Link
            v-if="pagination.prev_page_url"
            :href="pagination.prev_page_url"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Précédent
          </Link>
          <Link
            v-if="pagination.next_page_url"
            :href="pagination.next_page_url"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Suivant
          </Link>
        </div>
        
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Affichage de
              <span class="font-medium">{{ pagination.from }}</span>
              à
              <span class="font-medium">{{ pagination.to }}</span>
              sur
              <span class="font-medium">{{ pagination.total }}</span>
              résultats
            </p>
          </div>
          
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <Link
                v-for="link in pagination.links"
                :key="link.label"
                :href="link.url"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  link.active
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  link.url === null
                    ? 'cursor-not-allowed opacity-50'
                    : 'cursor-pointer'
                ]"
                v-html="link.label"
              />
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  MagnifyingGlassIcon,
  ChevronUpIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  columns: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  searchPlaceholder: {
    type: String,
    default: 'Rechercher...',
  },
  emptyMessage: {
    type: String,
    default: 'Aucun élément trouvé',
  },
  showActions: {
    type: Boolean,
    default: true,
  },
  showView: {
    type: Boolean,
    default: true,
  },
  showEdit: {
    type: Boolean,
    default: true,
  },
  showDelete: {
    type: Boolean,
    default: true,
  },
  viewRoute: {
    type: String,
    default: null,
  },
  editRoute: {
    type: String,
    default: null,
  },
  searchQuery: {
    type: String,
    default: '',
  },
  sortBy: {
    type: String,
    default: '',
  },
  sortOrder: {
    type: String,
    default: 'asc',
  },
})

const emit = defineEmits(['search', 'sort', 'delete'])

const searchQuery = ref(props.searchQuery)
const sortBy = ref(props.sortBy)
const sortOrder = ref(props.sortOrder)

const handleSearch = () => {
  emit('search', searchQuery.value)
}

const handleSort = (column) => {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = 'asc'
  }
  emit('sort', { column: sortBy.value, order: sortOrder.value })
}

const handleDelete = (item) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
    emit('delete', item)
  }
}

const getViewUrl = (item) => {
  if (props.viewRoute) {
    return route(props.viewRoute, item.id)
  }
  return '#'
}

const getEditUrl = (item) => {
  if (props.editRoute) {
    return route(props.editRoute, item.id)
  }
  return '#'
}

watch(() => props.searchQuery, (newValue) => {
  searchQuery.value = newValue
})

watch(() => props.sortBy, (newValue) => {
  sortBy.value = newValue
})

watch(() => props.sortOrder, (newValue) => {
  sortOrder.value = newValue
})
</script>
