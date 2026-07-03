const LOGO_FILES = new Set([
    'audi',
    'bmw',
    'chevrolet',
    'chrysler',
    'ford',
    'gmc',
    'honda',
    'humme',
    'hyundai',
    'infiniti',
    'jeep',
    'kia',
    'land-rover',
    'mazda',
    'mercedes-benz',
    'mitsubishi',
    'nissan',
    'opel',
    'suzuki',
    'tesla',
    'toyota',
    'volkswagen',
]);

const MAKE_ALIASES = {
    audi: 'audi',
    bmw: 'bmw',
    chevrolet: 'chevrolet',
    chevy: 'chevrolet',
    chrysler: 'chrysler',
    ford: 'ford',
    gmc: 'gmc',
    honda: 'honda',
    hummer: 'humme',
    hyundai: 'hyundai',
    infiniti: 'infiniti',
    jeep: 'jeep',
    kia: 'kia',
    'land rover': 'land-rover',
    'land-rover': 'land-rover',
    mazda: 'mazda',
    mercedes: 'mercedes-benz',
    'mercedes benz': 'mercedes-benz',
    'mercedes-benz': 'mercedes-benz',
    mitsubishi: 'mitsubishi',
    nissan: 'nissan',
    opel: 'opel',
    suzuki: 'suzuki',
    tesla: 'tesla',
    toyota: 'toyota',
    volkswagen: 'volkswagen',
    vw: 'volkswagen',
};

function slugifyMake(make) {
    if (typeof make !== 'string' || make.trim() === '') {
        return '';
    }

    return make
        .trim()
        .toLowerCase()
        .replace(/[_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function resolveLogoSlug(make) {
    const normalized = slugifyMake(make);

    if (! normalized) {
        return null;
    }

    if (MAKE_ALIASES[normalized]) {
        return MAKE_ALIASES[normalized];
    }

    const hyphenated = normalized.replace(/\s+/g, '-');

    if (LOGO_FILES.has(hyphenated)) {
        return hyphenated;
    }

    if (LOGO_FILES.has(normalized)) {
        return normalized;
    }

    return null;
}

/**
 * @param {object|null|undefined} vehicle
 * @returns {string|null} Public URL for brand SVG logo
 */
export function vehicleBrandLogoUrl(vehicle) {
    const make = vehicle?.make ?? vehicle?.raw_data?.make;
    const slug = resolveLogoSlug(make);

    if (! slug) {
        return null;
    }

    return `/car-logos/${slug}.svg`;
}

export function vehicleHasBrandLogo(vehicle) {
    return Boolean(vehicleBrandLogoUrl(vehicle));
}
