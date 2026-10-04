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
                <div id="map" />
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
import {Map, Popup, setWorkerUrl} from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import {isMapboxURL, transformMapboxUrl} from 'maplibregl-mapbox-request-transformer'
import {escape} from 'lodash';
import RelativeUTCTime from '../support/RelativeUTCTime';

setWorkerUrl(maplibreWorkerUrl);

const EMPTY_FEATURE_COLLECTION = {
    type: 'FeatureCollection',
    features: [],
};

const EMPTY_HIGHLIGHT_FILTER = ['==', ['to-string', ['get', 'id']], '__none__'];
const DEFAULT_BAND = 'All';
const DEFAULT_MODE = 'All';
const DEFAULT_SORT = 'newest';
const CONTACT_LIMIT = 200;
const DISTANCE_FORMATTER = new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 0,
});
const SORT_OPTIONS = [
    {value: 'newest', label: 'Newest'},
    {value: 'oldest', label: 'Oldest'},
    {value: 'distance_desc', label: 'Longest distance (DX)'},
    {value: 'distance_asc', label: 'Shortest distance'},
];
// Must stay in sync with LogbookEntryResource::bandColor() - one entry per
// band, matching hex-for-hex, since no two bands share a color there.
const BAND_LEGEND_ITEMS = [
    {label: '160M', color: '#1d4ed8'},
    {label: '80M', color: '#2563eb'},
    {label: '40M', color: '#0ea5e9'},
    {label: '30M', color: '#f59e0b'},
    {label: '20M', color: '#f97316'},
    {label: '17M', color: '#16a34a'},
    {label: '15M', color: '#22c55e'},
    {label: '12M', color: '#e11d48'},
    {label: '10M', color: '#ef4444'},
    {label: 'Other', color: '#64748b'},
];
const MODE_ICON_URLS = {
    'mode-phone': '/img/radio-icons/mode-phone.svg',
    'mode-digital': '/img/radio-icons/mode-digital.svg',
    'mode-sstv': '/img/radio-icons/mode-sstv.svg',
    'mode-other': '/img/radio-icons/mode-other.svg',
};
const MODE_LEGEND_ITEMS = [
    {label: 'Phone', url: MODE_ICON_URLS['mode-phone']},
    {label: 'Digital', url: MODE_ICON_URLS['mode-digital']},
    {label: 'SSTV', url: MODE_ICON_URLS['mode-sstv']},
    {label: 'Other', url: MODE_ICON_URLS['mode-other']},
];
const QTH_ICON_URL = '/img/radio-icons/qth-antenna.svg';
const ARC_POINT_COUNT = 48;
const GLOBE_ROTATION_IDLE_MS = 5000;
const GLOBE_ROTATION_MAX_ZOOM = 2.5;
const GLOBE_ROTATION_DEGREES_PER_SECOND = 2;

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
            activePopup: null,
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
            loadRequestId: 0,
            selectionAnimationId: 0,
            selectionAnimationTimers: [],
            globeRotationFrame: null,
            globeRotationLastFrame: null,
            globeRotationLastInteraction: -GLOBE_ROTATION_IDLE_MS,
            reducedMotionQuery: null,
        }
    },
    mounted() {
        this.reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        for (const event of ['pointerdown', 'wheel', 'keydown']) {
            this.$el.addEventListener(event, this.pauseGlobeRotation, {capture: true, passive: true});
        }
        this.initMap();
        this.fetchBands();
        this.fetchModes();
    },
    beforeUnmount() {
        cancelAnimationFrame(this.globeRotationFrame);
        for (const event of ['pointerdown', 'wheel', 'keydown']) {
            this.$el.removeEventListener(event, this.pauseGlobeRotation, true);
        }
        clearTimeout(this.searchTimeout);
        this.clearSelectionAnimation();
        this.activePopup?.remove();
        this.mapObject?.remove();
    },
    methods: {
        pauseGlobeRotation() {
            this.globeRotationLastInteraction = performance.now();
        },
        rotateIdleGlobe(timestamp) {
            const elapsed = this.globeRotationLastFrame === null
                ? 0
                : Math.min(timestamp - this.globeRotationLastFrame, 100);
            this.globeRotationLastFrame = timestamp;
            const map = this.mapObject;

            if (
                this.loaded
                && map
                && this.selectedContactId === null
                && map.getZoom() <= GLOBE_ROTATION_MAX_ZOOM
                && !map.isMoving()
                && !document.hidden
                && !this.reducedMotionQuery?.matches
                && timestamp - this.globeRotationLastInteraction >= GLOBE_ROTATION_IDLE_MS
            ) {
                const center = map.getCenter();
                const longitude = center.lng + GLOBE_ROTATION_DEGREES_PER_SECOND * elapsed / 1000;
                map.jumpTo({
                    center: [((longitude + 180) % 360 + 360) % 360 - 180, center.lat],
                });
            }

            this.globeRotationFrame = requestAnimationFrame(this.rotateIdleGlobe);
        },
        initMap() {
            const transformRequest = (url, resourceType) => {
                if (isMapboxURL(url)) {
                    return transformMapboxUrl(url, resourceType, this.mapboxKey)
                }

                return {url}
            }

            this.mapObject = new Map({
                container: 'map',
                center: [this.config.lng, this.config.lat],
                zoom: this.config.zoom,
                transformRequest,
            });
            this.mapObject.on('load', () => {
                this.loaded = true;
                this.onMapLoad(this.mapObject)
                this.globeRotationFrame = requestAnimationFrame(this.rotateIdleGlobe);
            });
            this.mapObject.on('error', (event) => {
                console.error(event.error ?? event);
            });
            this.mapObject.on('styleimagemissing', (event) => {
                this.registerMissingStyleImage(this.mapObject, event.id);
            });
            this.mapObject.setStyle('mapbox://styles/mapbox/outdoors-v12', {
                transformStyle: (_previousStyle, nextStyle) => ({
                    ...nextStyle,
                    projection: {type: 'globe'},
                    sky: {
                        'sky-color': 'transparent',
                        'horizon-color': 'transparent',
                        'fog-color': 'transparent',
                        'fog-ground-blend': 0,
                        'horizon-fog-blend': 0,
                        'atmosphere-blend': 0.8,
                    },
                }),
            });
        },
        loadQsos() {
            const requestId = ++this.loadRequestId;
            this.loadingQsos = true;

            axios.get(this.apiUrl).then(response => {
                if (requestId !== this.loadRequestId) {
                    return;
                }

                if (response.status === 200) {
                    this.contacts = response.data ?? EMPTY_FEATURE_COLLECTION;
                    this.resultMeta = response.data?.meta ?? {
                        total: this.contacts?.features?.length ?? 0,
                        returned: this.contacts?.features?.length ?? 0,
                        limit: CONTACT_LIMIT,
                        last_imported_at: null,
                    };
                    this.updateMap();
                    this.clearMissingSelection();
                }
            }).catch(error => {
                console.error(error);
            }).finally(() => {
                if (requestId === this.loadRequestId) {
                    this.loadingQsos = false;
                }
            });
        },
        queueLoadQsos() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.loadQsos(), 250);
        },
        fetchBands() {
            axios.get('/api/radio/bands').then(response => {
                if (response.status === 200) {
                    this.bands = response.data.filter(Boolean);
                }
            });
        },
        fetchModes() {
            axios.get('/api/radio/modes').then(response => {
                if (response.status === 200) {
                    this.modes = response.data.filter(Boolean);
                }
            });
        },
        updateMap() {
            const source = this.mapObject?.getSource?.('qsos');

            if (source) {
                source.setData(this.contacts);
            }

            this.updateMapHighlight();
            this.updateSelectedQsoPath();
        },
        async onMapLoad(map) {
            await this.registerMapIcons(map);
            map.addSource('qsos', {
                type: 'geojson',
                data: EMPTY_FEATURE_COLLECTION,
            });
            map.addSource('qth', {
                type: 'geojson',
                data: this.qthFeatureCollection(),
            });
            map.addSource('selected-qso-path', {
                type: 'geojson',
                data: EMPTY_FEATURE_COLLECTION,
            });

            const recencyScore = () => ['to-number', ['get', 'recency_score'], 0.35];
            map.addLayer({
                'id': 'selected-qso-path-casing',
                'type': 'line',
                'source': 'selected-qso-path',
                'paint': {
                    'line-color': '#ffffff',
                    'line-width': 8,
                    'line-opacity': 0.88,
                },
            });
            map.addLayer({
                'id': 'selected-qso-path',
                'type': 'line',
                'source': 'selected-qso-path',
                'paint': {
                    'line-color': '#0f766e',
                    'line-width': 4,
                    'line-opacity': 0.9,
                    'line-dasharray': [1.5, 1],
                },
            });
            map.addLayer({
                'id': 'qso-recency',
                'type': 'circle',
                'source': 'qsos',
                'paint': {
                    'circle-radius': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 4,
                        0.5, 9,
                        1, 18,
                    ],
                    'circle-color': ['to-color', ['get', 'band_color'], '#64748b'],
                    'circle-opacity': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.08,
                        0.5, 0.18,
                        1, 0.4,
                    ],
                    'circle-blur': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.9,
                        1, 1.35,
                    ],
                    'circle-stroke-color': ['to-color', ['get', 'band_color'], '#64748b'],
                    'circle-stroke-opacity': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0,
                        1, 0.55,
                    ],
                    'circle-stroke-width': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0,
                        1, 1.5,
                    ],
                },
            });
            map.addLayer({
                'id': 'qso-highlight',
                'type': 'circle',
                'source': 'qsos',
                'filter': EMPTY_HIGHLIGHT_FILTER,
                'paint': {
                    'circle-radius': 24,
                    'circle-color': '#0f766e',
                    'circle-opacity': 0.18,
                    'circle-stroke-color': '#0f766e',
                    'circle-stroke-opacity': 0.65,
                    'circle-stroke-width': 2,
                },
            });
            map.addLayer({
                'id': 'qso-badge',
                'type': 'circle',
                'source': 'qsos',
                'paint': {
                    'circle-radius': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 10,
                        0.6, 12,
                        1, 15,
                    ],
                    'circle-color': ['to-color', ['get', 'band_color'], '#64748b'],
                    'circle-opacity': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.55,
                        0.5, 0.8,
                        1, 1,
                    ],
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 1.5,
                    'circle-stroke-opacity': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.55,
                        0.5, 0.8,
                        1, 1,
                    ],
                },
            });
            map.addLayer({
                'id': 'qsos',
                'type': 'symbol',
                'source': 'qsos',
                'layout': {
                    'icon-image': ['coalesce', ['image', ['get', 'mode_icon']], ['image', 'mode-other']],
                    'icon-size': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.3,
                        0.6, 0.38,
                        1, 0.48,
                    ],
                    'icon-allow-overlap': true,
                },
                'paint': {
                    'icon-opacity': [
                        'interpolate',
                        ['linear'],
                        recencyScore(),
                        0, 0.35,
                        0.5, 0.7,
                        1, 1,
                    ],
                }
            });
            map.addLayer({
                'id': 'qth',
                'type': 'symbol',
                'source': 'qth',
                'layout': {
                    'icon-image': 'qth-antenna',
                    'icon-size': 0.4,
                    'icon-allow-overlap': true,
                    'text-field': ['get', 'label'],
                    'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                    'text-size': 12,
                    'text-anchor': 'top',
                    'text-offset': [0, 1.45],
                    'text-allow-overlap': true,
                },
                'paint': {
                    'text-color': '#0f172a',
                    'text-halo-color': '#ffffff',
                    'text-halo-width': 1.25,
                },
            });

            const qsoLayers = ['qsos', 'qso-badge', 'qso-highlight', 'qso-recency'];
            map.on('click', (event) => {
                const features = map.queryRenderedFeatures(event.point, {layers: qsoLayers});
                if (features.length === 0) {
                    this.deselectContact();
                    return;
                }

                const feature = features.find((feature) => feature.layer.id === 'qsos') ?? features[0];
                this.selectContact(feature, {
                    scrollRow: true,
                    popupLngLat: event.lngLat,
                    animate: true,
                });
            });
            map.on('mousemove', (event) => {
                const features = map.queryRenderedFeatures(event.point, {layers: qsoLayers});
                map.getCanvas().style.cursor = features.length > 0 ? 'pointer' : '';
            });
            map.on('mouseout', () => {
                map.getCanvas().style.cursor = '';
            });
            this.loadQsos();
        },
        registerMapIcons(map) {
            return Promise.all([
                ...Object.entries(MODE_ICON_URLS).map(([name, url]) => this.registerSvgIcon(map, name, url)),
                this.registerSvgIcon(map, 'qth-antenna', QTH_ICON_URL),
            ]);
        },
        registerMissingStyleImage(map, name) {
            const url = MODE_ICON_URLS[name] ?? (name === 'qth-antenna' ? QTH_ICON_URL : null);

            if (url !== null) {
                this.registerSvgIcon(map, name, url);
            }
        },
        registerSvgIcon(map, name, url) {
            if (map.hasImage(name)) {
                return Promise.resolve();
            }

            return new Promise((resolve) => {
                const timeout = window.setTimeout(resolve, 1000);
                const image = new Image(128, 128);
                image.onload = () => {
                    window.clearTimeout(timeout);

                    if (! map.hasImage(name)) {
                        map.addImage(name, image, {pixelRatio: 2});
                    }

                    resolve();
                };
                image.onerror = (error) => {
                    window.clearTimeout(timeout);
                    console.error(error);
                    resolve();
                };
                image.src = url;
            });
        },
        qthFeatureCollection() {
            const coordinates = this.qthCoordinates();

            if (coordinates === null) {
                return EMPTY_FEATURE_COLLECTION;
            }

            return {
                type: 'FeatureCollection',
                features: [
                    {
                        type: 'Feature',
                        geometry: {
                            type: 'Point',
                            coordinates,
                        },
                        properties: {
                            label: this.qth?.label ?? 'Approx. QTH',
                        },
                    },
                ],
            };
        },
        qthCoordinates() {
            const lat = Number(this.qth?.lat);
            const lng = Number(this.qth?.lng);

            if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                return null;
            }

            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                return null;
            }

            return [lng, lat];
        },
        selectContactFromTable(feature) {
            this.selectContact(feature, {
                fly: true,
                scrollMap: true,
                animate: true,
            });
        },
        selectContact(feature, options = {}) {
            this.pauseGlobeRotation();
            this.selectedContactId = this.contactId(feature);
            this.updateMapHighlight();
            this.updateSelectedQsoPath(feature);

            if (options.scrollRow) {
                this.$nextTick(() => {
                    document.getElementById(this.contactRowId(feature))?.scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth',
                    });
                });
            }

            if (options.scrollMap) {
                this.$nextTick(() => {
                    document.getElementById('qso-map-shell')?.scrollIntoView({
                        block: 'start',
                        behavior: 'smooth',
                    });
                });
            }

            this.focusFeatureOnMap(feature, options);
        },
        deselectContact() {
            if (this.selectedContactId === null) {
                return;
            }

            this.selectedContactId = null;
            this.pauseGlobeRotation();
            this.clearSelectionAnimation();
            this.activePopup?.remove();
            this.updateSelectedQsoPath();
            this.updateMapHighlight();
        },
        updateSelectedQsoPath(feature = null) {
            const source = this.mapObject?.getSource?.('selected-qso-path');

            if (! source) {
                return;
            }

            source.setData(this.selectedQsoPathFeatureCollection(feature));
        },
        selectedQsoPathFeatureCollection(feature = null) {
            const qthCoordinates = this.qthCoordinates();
            const selectedFeature = feature
                ?? this.tableContacts.find((contact) => this.contactId(contact) === this.selectedContactId);
            const selectedCoordinates = selectedFeature?.geometry?.coordinates;

            if (
                qthCoordinates === null
                || ! this.isCoordinatePair(selectedCoordinates)
            ) {
                return EMPTY_FEATURE_COLLECTION;
            }

            return {
                type: 'FeatureCollection',
                features: [
                    {
                        type: 'Feature',
                        geometry: {
                            type: 'LineString',
                            coordinates: this.generateArcCoordinates(qthCoordinates, selectedCoordinates),
                        },
                        properties: {},
                    },
                ],
            };
        },
        generateArcCoordinates(start, end) {
            const startLng = Number(start[0]);
            const startLat = Number(start[1]);
            const endLng = this.unwrapLongitude(startLng, end[0]);
            const endLat = Number(end[1]);
            const dx = endLng - startLng;
            const dy = endLat - startLat;
            const distance = Math.sqrt((dx * dx) + (dy * dy));

            if (distance === 0) {
                return [start, end];
            }

            const curve = Math.min(distance * 0.22, 16);
            let normalLng = -dy / distance;
            let normalLat = dx / distance;

            if (normalLat < 0) {
                normalLng *= -1;
                normalLat *= -1;
            }

            const controlLng = startLng + (dx / 2) + (normalLng * curve);
            const controlLat = startLat + (dy / 2) + (normalLat * curve);
            const coordinates = [];

            for (let index = 0; index <= ARC_POINT_COUNT; index += 1) {
                const t = index / ARC_POINT_COUNT;
                const inverse = 1 - t;
                const lng = (inverse * inverse * startLng)
                    + (2 * inverse * t * controlLng)
                    + (t * t * endLng);
                const lat = (inverse * inverse * startLat)
                    + (2 * inverse * t * controlLat)
                    + (t * t * endLat);

                coordinates.push([lng, lat]);
            }

            return coordinates;
        },
        unwrapLongitude(originLng, targetLng) {
            let lng = Number(targetLng);

            while (Math.abs(lng - originLng) > 180) {
                lng += lng > originLng ? -360 : 360;
            }

            return lng;
        },
        isCoordinatePair(value) {
            return Array.isArray(value)
                && value.length >= 2
                && Number.isFinite(Number(value[0]))
                && Number.isFinite(Number(value[1]));
        },
        focusFeatureOnMap(feature, options = {}) {
            if (! this.mapObject || ! feature?.geometry?.coordinates) {
                return;
            }

            const coordinates = this.popupCoordinates(feature, options.popupLngLat);
            const qthCoordinates = this.qthCoordinates();

            if (options.animate && qthCoordinates !== null && this.isCoordinatePair(coordinates)) {
                this.animateSelectedContact(feature, qthCoordinates, coordinates);
                return;
            }

            if (options.fly) {
                this.mapObject.flyTo({
                    center: coordinates,
                    zoom: Math.max(this.mapObject.getZoom(), 5),
                    essential: true,
                });
            }

            this.openPopup(feature, coordinates);
        },
        animateSelectedContact(feature, qthCoordinates, contactCoordinates) {
            this.clearSelectionAnimation();
            this.activePopup?.remove();

            const animationId = ++this.selectionAnimationId;
            const qthLng = Number(qthCoordinates[0]);
            const qthLat = Number(qthCoordinates[1]);
            const contactLng = Number(contactCoordinates[0]);
            const contactLat = Number(contactCoordinates[1]);

            if (qthLng === contactLng && qthLat === contactLat) {
                this.mapObject.flyTo({
                    center: contactCoordinates,
                    zoom: Math.max(this.mapObject.getZoom(), 5.75),
                    duration: 650,
                    essential: true,
                });
                this.queueSelectionAnimation(() => this.openPopup(feature, contactCoordinates), 675);
                return;
            }

            this.mapObject.fitBounds(this.selectionBounds(qthCoordinates, contactCoordinates), {
                padding: {
                    top: 96,
                    right: 96,
                    bottom: 96,
                    left: 96,
                },
                maxZoom: 4.75,
                duration: 850,
                essential: true,
            });

            this.queueSelectionAnimation(() => {
                if (animationId !== this.selectionAnimationId) {
                    return;
                }

                this.mapObject.easeTo({
                    center: contactCoordinates,
                    zoom: Math.max(this.mapObject.getZoom(), 5.75),
                    duration: 700,
                    essential: true,
                });
            }, 875);

            this.queueSelectionAnimation(() => {
                if (animationId === this.selectionAnimationId) {
                    this.openPopup(feature, contactCoordinates);
                }
            }, 1525);
        },
        selectionBounds(start, end) {
            const startLng = Number(start[0]);
            const startLat = Number(start[1]);
            const endLng = this.unwrapLongitude(startLng, end[0]);
            const endLat = Number(end[1]);

            return [
                [Math.min(startLng, endLng), Math.min(startLat, endLat)],
                [Math.max(startLng, endLng), Math.max(startLat, endLat)],
            ];
        },
        queueSelectionAnimation(callback, delay) {
            const timer = window.setTimeout(() => {
                this.selectionAnimationTimers = this.selectionAnimationTimers.filter((item) => item !== timer);
                callback();
            }, delay);

            this.selectionAnimationTimers.push(timer);
        },
        clearSelectionAnimation() {
            this.selectionAnimationTimers.forEach((timer) => window.clearTimeout(timer));
            this.selectionAnimationTimers = [];
        },
        popupCoordinates(feature, lngLat = null) {
            const coordinates = feature.geometry.coordinates.slice();

            if (lngLat !== null) {
                while (Math.abs(lngLat.lng - coordinates[0]) > 180) {
                    coordinates[0] += lngLat.lng > coordinates[0] ? 360 : -360;
                }
            }

            return coordinates;
        },
        openPopup(feature, coordinates) {
            this.activePopup?.remove();
            this.activePopup = new Popup({closeOnClick: false})
                .setLngLat(coordinates)
                .setHTML(this.buildPopupDescription(feature.properties))
                .addTo(this.mapObject);
        },
        buildPopupDescription(properties) {
            const mode = escape(properties.mode);
            const modeLabel = escape(properties.mode_label);
            const band = escape(properties.band);
            const toCallsign = escape(properties.to_callsign);
            const location = escape(properties.display_location ?? properties.to_country ?? '');
            const date = escape(properties.qso_date ?? properties.created_at ?? '');
            const frequency = escape(properties.frequency);
            const rstReceived = escape(properties.rst_received);
            const toGrid = escape(properties.to_grid);
            const parkReference = escape(properties.park_reference);
            const parkName = escape(properties.park_name);
            const parkLocation = escape(properties.park_location);
            const distance = escape(this.formatDistance(
                properties.distance,
                properties.bearing_cardinal,
                properties.distance_estimated,
            ));
            const comments = String(properties.comments ?? '').trim();

            let html = '<div>';
            html += `<div><b>${mode} QSO w/ ${toCallsign}</b></div>`;
            html += `<div><b>Date:</b> ${date}</div>`;
            html += `<div><b>Band:</b> ${band}</div>`;
            if (modeLabel !== '' && modeLabel !== mode) {
                html += `<div><b>Mode Type:</b> ${modeLabel}</div>`;
            }
            html += `<div><b>Frequency:</b> ${frequency} MHz</div>`;

            if (location !== '') {
                html += `<div><b>Location:</b> ${location}</div>`;
            }
            if (properties.category === 'POTA' && (parkReference !== '' || parkName !== '' || parkLocation !== '')) {
                const parkDetails = [parkReference, parkName, parkLocation].filter(Boolean).join(' - ');
                html += `<div><b>POTA:</b> ${parkDetails}</div>`;
            }
            if (rstReceived !== '') {
                html += `<div><b>RST Received:</b> ${rstReceived}</div>`;
            }
            if (toGrid !== '') {
                html += `<div><b>Grid:</b> ${toGrid}</div>`;
            }
            if (distance !== '-') {
                html += `<div><b>Distance:</b> ${distance}</div>`;
            }
            if (comments !== '') {
                html += `<div><b>Comments:</b> ${escape(comments)}</div>`;
            }

            html += '</div>';
            return html;
        },
        highlightFeature(feature) {
            this.hoveredContactId = this.contactId(feature);
            this.updateMapHighlight();
        },
        clearHighlight() {
            this.hoveredContactId = null;
            this.updateMapHighlight();
        },
        updateMapHighlight() {
            if (! this.mapObject?.getLayer?.('qso-highlight')) {
                return;
            }

            const contactId = this.hoveredContactId || this.selectedContactId;
            this.mapObject.setFilter('qso-highlight', contactId
                ? ['==', ['to-string', ['get', 'id']], contactId]
                : EMPTY_HIGHLIGHT_FILTER
            );
        },
        clearMissingSelection() {
            if (
                this.selectedContactId !== null
                && ! this.tableContacts.some((feature) => this.contactId(feature) === this.selectedContactId)
            ) {
                this.deselectContact();
                return;
            }

            this.updateMapHighlight();
        },
        contactId(feature) {
            return String(feature?.properties?.id ?? '');
        },
        contactKey(feature, index) {
            return this.contactId(feature) || `${feature?.properties?.qso_date ?? 'contact'}-${index}`;
        },
        contactRowId(feature) {
            return `qso-row-${this.contactId(feature)}`;
        },
        rowClass(feature) {
            return {
                'bg-brand-100': this.contactId(feature) === this.selectedContactId,
                'bg-white': this.contactId(feature) !== this.selectedContactId,
            };
        },
        parseDate(value) {
            if (! value) {
                return null;
            }

            const date = new Date(String(value).replace(' ', 'T'));

            return Number.isNaN(date.getTime()) ? null : date;
        },
        formatDate(value) {
            const date = this.parseDate(value);

            if (date === null) {
                return value || '-';
            }

            return new Intl.DateTimeFormat(undefined, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            }).format(date);
        },
        formatImportSummary(value) {
            return RelativeUTCTime.format(value);
        },
        formatRelativeAge(ageDays) {
            if (ageDays === null || ageDays === undefined || ageDays === '') {
                return '';
            }

            const days = Number(ageDays);

            if (! Number.isFinite(days)) {
                return '';
            }

            if (days < 1) {
                return 'today';
            }

            if (days === 1) {
                return '1 day ago';
            }

            if (days < 30) {
                return `${days} days ago`;
            }

            if (days < 365) {
                const months = Math.floor(days / 30);

                return months === 1 ? '1 month ago' : `${months} months ago`;
            }

            const years = Math.floor(days / 365);

            return years === 1 ? '1 year ago' : `${years} years ago`;
        },
        formatDistance(value, cardinal = null, estimated = false) {
            if (value === null || value === undefined || value === '') {
                return cardinal || '-';
            }

            const numericDistance = Number(value);
            const distance = Number.isFinite(numericDistance)
                ? DISTANCE_FORMATTER.format(numericDistance)
                : String(value);
            const direction = cardinal ? ` ${cardinal}` : '';
            const prefix = estimated ? '~' : '';

            return `${prefix}${distance} mi${direction}`;
        },
        hasBearing(properties) {
            return properties?.bearing_degrees !== null
                && properties?.bearing_degrees !== undefined
                && properties?.bearing_cardinal;
        },
        directionArrowStyle(degrees) {
            const bearing = Number(degrees);

            if (! Number.isFinite(bearing)) {
                return {};
            }

            return {
                transform: `rotate(${bearing}deg)`,
            };
        },
        formatBearingTitle(properties) {
            const bearing = Number(properties?.bearing_degrees);
            const cardinal = properties?.bearing_cardinal;

            if (! Number.isFinite(bearing) || ! cardinal) {
                return '';
            }

            return `Bearing ${cardinal} (${Math.round(bearing)}°)`;
        },
        clearFilters() {
            this.searchTerm = '';
            this.currentBand = DEFAULT_BAND;
            this.currentMode = DEFAULT_MODE;
            this.currentSort = DEFAULT_SORT;
        },
        toggleHelp() {
            this.showHelp = !this.showHelp;
        }
    },
    watch: {
        currentBand() {
            this.loadQsos();
        },
        currentMode() {
            this.loadQsos();
        },
        currentSort() {
            this.loadQsos();
        },
        searchTerm() {
            this.queueLoadQsos();
        }
    },
    computed: {
        apiUrl() {
            const params = new URLSearchParams({
                limit: String(CONTACT_LIMIT),
            });

            if (this.searchTerm !== '') {
                params.set('search', this.searchTerm);
            }

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

            if (this.isResultLimited) {
                return `Showing ${this.qsoCount} of ${total} contacts`;
            }

            return `${this.qsoCount} contacts`;
        },
        isResultLimited() {
            return Number(this.resultMeta?.total ?? 0) > this.qsoCount;
        },
        currentSortLabel() {
            return this.sortOptions.find((option) => option.value === this.currentSort)?.label ?? 'Newest';
        },
        lastImportSummary() {
            if (! this.resultMeta?.last_imported_at) {
                return 'Import status unknown';
            }

            return this.formatImportSummary(this.resultMeta.last_imported_at);
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
        }
    }
}
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
