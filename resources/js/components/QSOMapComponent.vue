<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
            <div class="grow text-gray-500">
                <p>Searchable amateur radio contacts from my logbook.</p>
                <p class="text-sm" v-text="lastImportSummary" />
            </div>
            <a @click.prevent="toggleHelp" class="btn-primary inline-block shrink-0 self-start sm:self-auto">
                What's This?
            </a>
        </div>

        <p v-if="showHelp" class="p-2">
            One of my hobbies is <a target="_blank" rel="noopener noreferrer" href="https://en.wikipedia.org/wiki/Amateur_radio" class="text-brand-700 underline hover:text-brand-900">Ham Radio</a>. This logbook shows stations and parks I have contacted, with a map view for each contact.
        </p>

        <div class="border border-gray-200 bg-white p-3">
            <div class="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_10rem_10rem_14rem_auto] lg:items-end">
                <label class="block">
                    <span class="block text-sm font-semibold text-gray-700">Search logbook</span>
                    <input
                        v-model.trim="searchTerm"
                        type="search"
                        class="form-control mt-1 w-full"
                        placeholder="Callsign, grid, country, comments"
                    />
                </label>

                <label class="block">
                    <span class="block text-sm font-semibold text-gray-700">Band</span>
                    <select class="form-control mt-1 w-full" v-model="currentBand">
                        <option v-for="band in bandOptions" :value="band" :key="band" v-text="band" />
                    </select>
                </label>

                <label class="block">
                    <span class="block text-sm font-semibold text-gray-700">Mode</span>
                    <select class="form-control mt-1 w-full" v-model="currentMode">
                        <option v-for="mode in modeOptions" :value="mode" :key="mode" v-text="mode" />
                    </select>
                </label>

                <label class="block">
                    <span class="block text-sm font-semibold text-gray-700">Sort</span>
                    <select class="form-control mt-1 w-full" v-model="currentSort">
                        <option
                            v-for="option in sortOptions"
                            :value="option.value"
                            :key="option.value"
                            v-text="option.label"
                        />
                    </select>
                </label>

                <div class="text-sm lg:text-right">
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="text-brand-700 underline hover:text-brand-900"
                        @click="clearFilters"
                    >
                        Reset view
                    </button>
                </div>
            </div>
        </div>

        <div v-show="!loaded" class="text-gray-500">
            Please wait, map loading...
        </div>

        <div class="space-y-4">
            <section id="qso-map-shell" class="min-w-0 border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2">
                    <h2 class="text-base font-semibold text-gray-900">Map View</h2>
                    <span class="text-sm text-gray-500">Newer contacts are larger and brighter</span>
                </div>
                <div id="map" ref="mapContainer" />
                <div class="border-t border-gray-200 px-3 py-2">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-600">
                        <span class="font-semibold text-gray-900">Legend</span>
                        <span
                            v-for="item in bandLegendItems"
                            :key="item.label"
                            class="inline-flex items-center gap-1.5"
                        >
                            <span
                                class="legend-band-swatch"
                                :style="{backgroundColor: item.color}"
                                aria-hidden="true"
                            />
                            <span v-text="item.label" />
                        </span>
                        <span class="hidden h-4 border-l border-gray-300 sm:inline-block" aria-hidden="true" />
                        <span
                            v-for="item in modeLegendItems"
                            :key="item.label"
                            class="inline-flex items-center gap-1.5"
                        >
                            <span class="legend-mode-icon" aria-hidden="true">
                                <img :src="item.url" alt="" />
                            </span>
                            <span v-text="item.label" />
                        </span>
                    </div>
                </div>
            </section>

            <section class="min-w-0 border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2">
                    <h2 class="text-base font-semibold text-gray-900">Logbook Contacts</h2>
                    <span v-if="loadingQsos" class="text-sm text-gray-500">Loading...</span>
                </div>

                <div class="qso-table-scroll overflow-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th scope="col" class="px-3 py-2 font-semibold">Date</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Station</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Band</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Mode</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Grid</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Distance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr
                                v-for="(contact, index) in tableContacts"
                                :id="contactRowId(contact)"
                                :key="contactKey(contact, index)"
                                :class="rowClass(contact)"
                                class="cursor-pointer transition-colors hover:bg-brand-50"
                                @click="selectContactFromTable(contact)"
                                @mouseenter="highlightFeature(contact)"
                                @mouseleave="clearHighlight"
                            >
                                <td class="whitespace-nowrap px-3 py-3">
                                    <div class="text-gray-700" v-text="formatDate(contact.properties.qso_date)" />
                                    <div class="text-xs text-gray-500" v-text="formatRelativeAge(contact.properties.age_days)" />
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold text-gray-900" v-text="contact.properties.to_callsign" />
                                    <div class="text-xs text-gray-500" v-text="contact.properties.display_location || contact.properties.to_country" />
                                    <div v-if="contact.properties.category === 'POTA'" class="mt-1 inline-block bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                        POTA
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-gray-700" v-text="contact.properties.band" />
                                <td class="whitespace-nowrap px-3 py-3 text-gray-700" v-text="contact.properties.mode" />
                                <td class="whitespace-nowrap px-3 py-3 text-gray-700" v-text="contact.properties.to_grid || '-'" />
                                <td class="whitespace-nowrap px-3 py-3 text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="hasBearing(contact.properties)"
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-700"
                                            :title="formatBearingTitle(contact.properties)"
                                        >
                                            <span
                                                class="direction-arrow"
                                                :style="directionArrowStyle(contact.properties.bearing_degrees)"
                                                aria-hidden="true"
                                            >↑</span>
                                            <span class="sr-only" v-text="formatBearingTitle(contact.properties)" />
                                        </span>
                                        <span
                                            v-text="formatDistance(
                                                contact.properties.distance,
                                                contact.properties.bearing_cardinal,
                                                contact.properties.distance_estimated
                                            )"
                                        />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tableContacts.length === 0">
                                <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                    No contacts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-3 py-2 text-right text-sm text-gray-600">
                    <div class="font-semibold text-gray-900" v-text="resultSummary" />
                    <div v-if="isResultLimited" class="text-xs text-gray-500">
                        First <span v-text="resultMeta.limit" /> by <span v-text="currentSortLabel" />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import {markRaw} from 'vue';
import axios from '../bootstrap';
import {Map, Popup, setWorkerUrl} from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import {isMapboxURL, transformMapboxUrl} from 'maplibregl-mapbox-request-transformer';
import RelativeUTCTime from '../support/RelativeUTCTime';
import {EMPTY_FEATURE_COLLECTION, selectedPath} from '../support/qso-map/geometry.mjs';
import {
    DEFAULT_BAND, DEFAULT_MODE, DEFAULT_SORT, CONTACT_LIMIT, SORT_OPTIONS,
    BAND_LEGEND_ITEMS, MODE_LEGEND_ITEMS, contactId, contactKey, contactRowId,
    formatDate, formatRelativeAge, formatDistance, hasBearing, directionArrowStyle, formatBearingTitle,
} from '../support/qso-map/presentation.mjs';
import {EMPTY_HIGHLIGHT_FILTER} from '../support/qso-map/style.mjs';
import {createMapLifecycle} from '../support/qso-map/mapLifecycle.mjs';
import {createGlobeRotation} from '../support/qso-map/globeRotation.mjs';
import {createSelection} from '../support/qso-map/selection.mjs';
import {createRequests} from '../support/qso-map/requests.mjs';

setWorkerUrl(maplibreWorkerUrl);

export default {
    name: 'QSOMapComponent',
    props: ['mapboxKey', 'config', 'qth'],
    data() {
        return {
            loaded: false,
            loadingQsos: false,
            contacts: EMPTY_FEATURE_COLLECTION,
            bands: [],
            modes: [],
            mapObject: null,
            mapLifecycle: null,
            rotation: null,
            selection: null,
            requests: null,
            disposed: false,
            currentMode: DEFAULT_MODE,
            currentBand: DEFAULT_BAND,
            currentSort: DEFAULT_SORT,
            sortOptions: SORT_OPTIONS,
            searchTerm: '',
            resultMeta: {
                total: 0,
                returned: 0,
                limit: CONTACT_LIMIT,
                last_imported_at: null,
            },
            selectedContactId: null,
            hoveredContactId: null,
            showHelp: false,
            searchTimeout: null,
        };
    },
    mounted() {
        this.requests = markRaw(createRequests({get: (url, options) => axios.get(url, options)}));
        this.initMap();
        this.requests.load('bands', '/api/radio/bands', data => { this.bands = data.filter(Boolean); });
        this.requests.load('modes', '/api/radio/modes', data => { this.modes = data.filter(Boolean); });
    },
    beforeUnmount() {
        this.disposed = true;
        clearTimeout(this.searchTimeout);
        this.requests?.dispose();
        this.rotation?.dispose();
        this.selection?.dispose();
        this.mapLifecycle?.dispose();
        this.mapObject = null;
    },
    methods: {
        contactId,
        contactKey,
        contactRowId,
        formatDate,
        formatRelativeAge,
        formatDistance,
        hasBearing,
        directionArrowStyle,
        formatBearingTitle,
        initMap() {
            const map = markRaw(new Map({
                container: this.$refs.mapContainer,
                center: [this.config.lng, this.config.lat],
                zoom: this.config.zoom,
                transformRequest: (url, resourceType) => isMapboxURL(url)
                    ? transformMapboxUrl(url, resourceType, this.mapboxKey)
                    : {url},
            }));
            this.mapObject = map;
            this.rotation = markRaw(createGlobeRotation({
                map, root: this.$el, hasSelection: () => this.selectedContactId !== null,
            }));
            this.selection = markRaw(createSelection({
                map, getQth: () => this.qth, createPopup: () => new Popup({closeOnClick: false}),
            }));
            this.mapLifecycle = markRaw(createMapLifecycle({
                map,
                qth: this.qth,
                onSelect: this.selectContact,
                onDeselect: this.deselectContact,
                onReady: () => {
                    this.loaded = true;
                    this.updateMap();
                    this.rotation.start();
                    this.loadQsos();
                },
            }));
        },
        loadQsos() {
            if (this.disposed || !this.requests) return;
            clearTimeout(this.searchTimeout);
            this.loadingQsos = true;
            this.requests.load('qsos', this.apiUrl, data => {
                this.contacts = data ?? EMPTY_FEATURE_COLLECTION;
                this.resultMeta = data?.meta ?? {
                    total: this.qsoCount,
                    returned: this.qsoCount,
                    limit: CONTACT_LIMIT,
                    last_imported_at: null,
                };
                this.clearMissingSelection();
                this.updateMap();
            }, () => { this.loadingQsos = false; });
        },
        queueLoadQsos() {
            clearTimeout(this.searchTimeout);
            // An old response must not overwrite results during the search debounce.
            this.requests?.invalidate('qsos');
            this.searchTimeout = setTimeout(() => this.loadQsos(), 250);
        },
        updateMap() {
            this.mapObject?.getSource?.('qsos')?.setData(this.contacts);
            this.updateMapHighlight();
            this.updateSelectedQsoPath();
        },
        selectContactFromTable(feature) {
            this.selectContact(feature, {fly: true, scrollMap: true, animate: true});
        },
        selectContact(feature, options = {}) {
            if (this.disposed) return;
            this.rotation?.pause();
            this.selectedContactId = contactId(feature);
            this.updateMapHighlight();
            this.updateSelectedQsoPath(feature);
            if (options.scrollRow || options.scrollMap) {
                this.$nextTick(() => {
                    if (this.disposed || this.selectedContactId !== contactId(feature)) return;
                    if (options.scrollRow) {
                        document.getElementById(contactRowId(feature))?.scrollIntoView({
                            block: 'nearest', behavior: 'smooth',
                        });
                    }
                    if (options.scrollMap) {
                        document.getElementById('qso-map-shell')?.scrollIntoView({
                            block: 'start', behavior: 'smooth',
                        });
                    }
                });
            }
            this.selection?.focus(feature, options);
        },
        deselectContact() {
            if (this.selectedContactId === null) return;
            this.selectedContactId = null;
            this.rotation?.pause();
            this.selection?.clear();
            this.updateSelectedQsoPath();
            this.updateMapHighlight();
        },
        updateSelectedQsoPath(feature = null) {
            const selectedFeature = feature
                ?? this.tableContacts.find(contact => contactId(contact) === this.selectedContactId);
            this.mapObject?.getSource?.('selected-qso-path')?.setData(selectedPath(this.qth, selectedFeature));
        },
        highlightFeature(feature) {
            this.hoveredContactId = contactId(feature);
            this.updateMapHighlight();
        },
        clearHighlight() {
            this.hoveredContactId = null;
            this.updateMapHighlight();
        },
        updateMapHighlight() {
            if (!this.mapObject?.getLayer?.('qso-highlight')) return;
            const id = this.hoveredContactId || this.selectedContactId;
            this.mapObject.setFilter('qso-highlight', id
                ? ['==', ['to-string', ['get', 'id']], id]
                : EMPTY_HIGHLIGHT_FILTER);
        },
        clearMissingSelection() {
            if (this.hoveredContactId !== null
                && !this.tableContacts.some(feature => contactId(feature) === this.hoveredContactId)) {
                this.hoveredContactId = null;
            }
            if (this.selectedContactId !== null
                && !this.tableContacts.some(feature => contactId(feature) === this.selectedContactId)) {
                this.deselectContact();
            }
        },
        rowClass(feature) {
            return {
                'bg-brand-100': contactId(feature) === this.selectedContactId,
                'bg-white': contactId(feature) !== this.selectedContactId,
            };
        },
        clearFilters() {
            this.searchTerm = '';
            this.currentBand = DEFAULT_BAND;
            this.currentMode = DEFAULT_MODE;
            this.currentSort = DEFAULT_SORT;
        },
        toggleHelp() {
            this.showHelp = !this.showHelp;
        },
    },
    watch: {
        currentBand() { this.loadQsos(); },
        currentMode() { this.loadQsos(); },
        currentSort() { this.loadQsos(); },
        searchTerm() { this.queueLoadQsos(); },
    },
    computed: {
        apiUrl() {
            const params = new URLSearchParams({limit: String(CONTACT_LIMIT)});
            if (this.searchTerm !== '') params.set('search', this.searchTerm);
            params.set('sort', this.currentSort);
            return `/api/radio/qsos/band/${encodeURIComponent(this.currentBand)}/mode/${encodeURIComponent(this.currentMode)}?${params.toString()}`;
        },
        tableContacts() {
            return this.contacts?.features ?? [];
        },
        qsoCount() {
            return this.tableContacts.length;
        },
        resultSummary() {
            const total = Number(this.resultMeta?.total ?? this.qsoCount);
            return this.isResultLimited ? `Showing ${this.qsoCount} of ${total} contacts` : `${this.qsoCount} contacts`;
        },
        isResultLimited() {
            return Number(this.resultMeta?.total ?? 0) > this.qsoCount;
        },
        currentSortLabel() {
            return this.sortOptions.find(option => option.value === this.currentSort)?.label ?? 'Newest';
        },
        lastImportSummary() {
            if (!this.resultMeta?.last_imported_at) return 'Import status unknown';
            return RelativeUTCTime.format(this.resultMeta?.last_imported_at);
        },
        bandOptions() {
            return ['All', ...this.bands];
        },
        modeOptions() {
            return ['All', ...this.modes];
        },
        bandLegendItems() {
            return BAND_LEGEND_ITEMS;
        },
        modeLegendItems() {
            return MODE_LEGEND_ITEMS;
        },
        hasActiveFilters() {
            return this.searchTerm !== ''
                || this.currentBand !== DEFAULT_BAND
                || this.currentMode !== DEFAULT_MODE
                || this.currentSort !== DEFAULT_SORT;
        },
    },
};
</script>

<style>
#map {
    height: 68vh;
    background-color: #030712;
    background-image:
        radial-gradient(circle at 17px 23px, rgba(255, 255, 255, 0.85) 0.8px, transparent 1.5px),
        radial-gradient(circle at 67px 83px, rgba(191, 219, 254, 0.65) 1px, transparent 1.7px),
        radial-gradient(circle at 113px 47px, rgba(255, 255, 255, 0.45) 0.6px, transparent 1.2px),
        radial-gradient(ellipse at 70% 20%, #172554 0%, transparent 65%);
    background-size: 173px 191px, 263px 277px, 337px 311px, 100% 100%;
}

.qso-table-scroll {
    max-height: 54vh;
}

.direction-arrow {
    display: inline-block;
    line-height: 1;
    transform-origin: center;
}

.legend-band-swatch {
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 9999px;
}

.legend-mode-icon {
    display: inline-flex;
    width: 1rem;
    height: 1rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background-color: #111827;
}

.legend-mode-icon img {
    width: 1rem;
    height: 1rem;
    display: block;
}

@media (max-width: 1279px) {
    #map {
        height: 58vh;
    }

    .qso-table-scroll {
        max-height: 60vh;
    }
}
</style>
