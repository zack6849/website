const DISTANCE_FORMATTER = new Intl.NumberFormat(undefined, {maximumFractionDigits: 0});

export const DEFAULT_BAND = 'All';
export const DEFAULT_MODE = 'All';
export const DEFAULT_SORT = 'newest';
export const CONTACT_LIMIT = 200;
export const SORT_OPTIONS = [
    {value: 'newest', label: 'Newest'},
    {value: 'oldest', label: 'Oldest'},
    {value: 'distance_desc', label: 'Longest distance (DX)'},
    {value: 'distance_asc', label: 'Shortest distance'},
];
// Keep these colors in sync with LogbookEntryResource::bandColor().
export const BAND_LEGEND_ITEMS = [
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
export const MODE_ICON_URLS = {
    'mode-phone': '/img/radio-icons/mode-phone.svg',
    'mode-digital': '/img/radio-icons/mode-digital.svg',
    'mode-sstv': '/img/radio-icons/mode-sstv.svg',
    'mode-other': '/img/radio-icons/mode-other.svg',
};
export const QTH_ICON_URL = '/img/radio-icons/qth-antenna.svg';
export const MODE_LEGEND_ITEMS = [
    {label: 'Phone', url: MODE_ICON_URLS['mode-phone']},
    {label: 'Digital', url: MODE_ICON_URLS['mode-digital']},
    {label: 'SSTV', url: MODE_ICON_URLS['mode-sstv']},
    {label: 'Other', url: MODE_ICON_URLS['mode-other']},
];

export function contactId(feature) {
    return String(feature?.properties?.id ?? '');
}

export function contactKey(feature, index) {
    return contactId(feature) || `${feature?.properties?.qso_date ?? 'contact'}-${index}`;
}

export function contactRowId(feature) {
    return `qso-row-${contactId(feature)}`;
}

export function formatDate(value) {
    if (!value) {
        return '-';
    }
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat(undefined, {
        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
    }).format(date);
}

export function formatRelativeAge(ageDays) {
    if (ageDays === null || ageDays === undefined || ageDays === '') {
        return '';
    }
    const days = Number(ageDays);
    if (!Number.isFinite(days)) return '';
    if (days < 1) return 'today';
    if (days === 1) return '1 day ago';
    if (days < 30) return `${days} days ago`;
    if (days < 365) {
        const months = Math.floor(days / 30);
        return months === 1 ? '1 month ago' : `${months} months ago`;
    }
    const years = Math.floor(days / 365);
    return years === 1 ? '1 year ago' : `${years} years ago`;
}

export function formatDistance(value, cardinal = null, estimated = false) {
    if (value === null || value === undefined || value === '') return cardinal || '-';
    const numeric = Number(value);
    const distance = Number.isFinite(numeric) ? DISTANCE_FORMATTER.format(numeric) : String(value);
    return `${estimated ? '~' : ''}${distance} mi${cardinal ? ` ${cardinal}` : ''}`;
}

export function hasBearing(properties) {
    return properties?.bearing_degrees !== null
        && properties?.bearing_degrees !== undefined
        && properties?.bearing_cardinal;
}

export function directionArrowStyle(degrees) {
    const bearing = Number(degrees);
    return Number.isFinite(bearing) ? {transform: `rotate(${bearing}deg)`} : {};
}

export function formatBearingTitle(properties) {
    const bearing = Number(properties?.bearing_degrees);
    const cardinal = properties?.bearing_cardinal;
    return Number.isFinite(bearing) && cardinal ? `Bearing ${cardinal} (${Math.round(bearing)}°)` : '';
}

// Same entities as lodash.escape; every API-supplied popup field passes through here.
export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
}

export function buildPopupDescription(properties) {
    const mode = escapeHtml(properties.mode);
    const modeLabel = escapeHtml(properties.mode_label);
    const location = escapeHtml(properties.display_location ?? properties.to_country ?? '');
    const parkDetails = [properties.park_reference, properties.park_name, properties.park_location]
        .map(escapeHtml).filter(Boolean).join(' - ');
    const distance = escapeHtml(formatDistance(
        properties.distance, properties.bearing_cardinal, properties.distance_estimated,
    ));
    const comments = String(properties.comments ?? '').trim();
    const field = (label, value) => `<div><b>${label}:</b> ${value}</div>`;

    let html = `<div><div><b>${mode} QSO w/ ${escapeHtml(properties.to_callsign)}</b></div>`;
    html += field('Date', escapeHtml(properties.qso_date ?? properties.created_at ?? ''));
    html += field('Band', escapeHtml(properties.band));
    if (modeLabel !== '' && modeLabel !== mode) html += field('Mode Type', modeLabel);
    html += field('Frequency', `${escapeHtml(properties.frequency)} MHz`);
    if (location !== '') html += field('Location', location);
    if (properties.category === 'POTA' && parkDetails !== '') html += field('POTA', parkDetails);
    if (escapeHtml(properties.rst_received) !== '') html += field('RST Received', escapeHtml(properties.rst_received));
    if (escapeHtml(properties.to_grid) !== '') html += field('Grid', escapeHtml(properties.to_grid));
    if (distance !== '-') html += field('Distance', distance);
    if (comments !== '') html += field('Comments', escapeHtml(comments));
    return `${html}</div>`;
}
