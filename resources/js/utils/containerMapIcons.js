import L from 'leaflet';

/**
 * Custom Leaflet divIcon markers for container tracking (Vinstack-style).
 */
export function createTrackingMarkerIcon(type) {
    const html = MARKER_HTML[type] ?? MARKER_HTML.waypoint;

    return L.divIcon({
        className: 'tracking-map-marker',
        html,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -20],
    });
}

const MARKER_HTML = {
    origin: `
        <span class="tm tm--origin" title="المنشأ">
            <i class="pi pi-arrow-up" aria-hidden="true"></i>
        </span>
    `,
    waypoint: `
        <span class="tm tm--waypoint" title="محطة ترانزيت">
            <i class="pi pi-arrow-right tm__arrow" aria-hidden="true"></i>
            <i class="pi pi-send tm__ship" aria-hidden="true"></i>
        </span>
    `,
    destination: `
        <span class="tm tm--destination" title="الوجهة">
            <i class="pi pi-circle" aria-hidden="true"></i>
        </span>
    `,
    current: `
        <span class="tm tm--current" title="الموقع الحالي">
            <i class="pi pi-car" aria-hidden="true"></i>
        </span>
    `,
};

export const MAP_LEGEND_ITEMS = [
    { key: 'origin', label: 'المنشأ', class: 'tm--origin' },
    { key: 'waypoint', label: 'محطة ترانزيت', class: 'tm--waypoint' },
    { key: 'destination', label: 'الوجهة', class: 'tm--destination' },
    { key: 'current', label: 'الموقع الحالي', class: 'tm--current' },
];
