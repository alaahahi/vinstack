import L from 'leaflet';

const CAR_SVG = `
<svg class="tm__car-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path fill="currentColor" d="M18.92 6.01C18.72 5.42 18.15 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v7c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-7l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
</svg>
`.trim();

/**
 * Custom Leaflet divIcon markers for container tracking (Vinstack-style).
 */
export function createTrackingMarkerIcon(type) {
    const html = MARKER_HTML[type] ?? MARKER_HTML.waypoint;
    const isCurrent = type === 'current';

    return L.divIcon({
        className: `tracking-map-marker${isCurrent ? ' tracking-map-marker--current' : ''}`,
        html,
        iconSize: isCurrent ? [42, 42] : [36, 36],
        iconAnchor: isCurrent ? [21, 21] : [18, 18],
        popupAnchor: [0, -22],
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
            <span class="tm__pulse" aria-hidden="true"></span>
            ${CAR_SVG}
        </span>
    `,
};

export const MAP_LEGEND_ITEMS = [
    { key: 'origin', label: 'المنشأ', class: 'tm--origin' },
    { key: 'waypoint', label: 'محطة ترانزيت', class: 'tm--waypoint' },
    { key: 'destination', label: 'الوجهة', class: 'tm--destination' },
    { key: 'current', label: 'الموقع الحالي', class: 'tm--current' },
];

export { CAR_SVG };
