import { scalarString } from './scalarString';

function vehicleRawString(vehicle, key) {
    return scalarString(vehicle?.raw_data?.[key]);
}

function formatVehicleDate(value) {
    if (! value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    return `${day}/${month}/${year}`;
}

export function vehicleTitle(vehicle) {
    const raw = vehicle?.raw_data ?? vehicle ?? {};

    return [raw.year ?? vehicle?.year, raw.make ?? vehicle?.make, raw.model ?? vehicle?.model]
        .filter(Boolean)
        .join(' ');
}

export function vehicleFuelType(vehicle) {
    return vehicleRawString(vehicle, 'fuel_type');
}

export function vehicleFuelClass(fuel) {
    const value = fuel?.toLowerCase() ?? '';

    if (value.includes('hybrid')) {
        return 'fuel-hybrid';
    }

    if (value.includes('electric') || value === 'ev') {
        return 'fuel-electric';
    }

    if (value.includes('gas') || value.includes('petrol') || value.includes('diesel')) {
        return 'fuel-gas';
    }

    return 'fuel-default';
}

export function vehicleLot(vehicle) {
    return vehicleRawString(vehicle, 'lot');
}

export function vehicleAuction(vehicle) {
    return vehicleRawString(vehicle, 'auction');
}

export function vehicleOrigin(vehicle) {
    return vehicleRawString(vehicle, 'loading_point');
}

export function vehicleDestination(vehicle) {
    return vehicleRawString(vehicle, 'destination');
}

export function vehicleVinstackStatus(vehicle) {
    return vehicleRawString(vehicle, 'status');
}

export function vehicleStatusClass(status) {
    const value = status?.toLowerCase() ?? '';

    if (value.includes('terminal')) {
        return 'status-terminal';
    }

    if (value.includes('purchase') || value.includes('new')) {
        return 'status-new';
    }

    if (value.includes('shipped') || value.includes('transit')) {
        return 'status-transit';
    }

    return 'status-default';
}

export function vehicleContainerRef(vehicle) {
    return vehicleRawString(vehicle, 'container_number');
}

export function vehicleBookingRef(vehicle) {
    return vehicleRawString(vehicle, 'booking_number');
}

const KEYS_ABSENT = new Set(['no keys', 'missing', 'no key', 'no', 'false']);

export function vehicleKeysInfo(vehicle) {
    const raw = vehicle?.raw_data?.keys;

    if (raw === null || raw === undefined) {
        return { label: null, present: null };
    }

    if (raw === false) {
        return { label: 'No Keys', present: false };
    }

    if (raw === true) {
        return { label: 'Present', present: true };
    }

    const keys = String(raw).trim();

    if (! keys) {
        return { label: null, present: null };
    }

    const lower = keys.toLowerCase();

    if (lower === 'present') {
        return { label: 'Present', present: true };
    }

    if (KEYS_ABSENT.has(lower)) {
        return { label: 'No Keys', present: false };
    }

    return { label: keys, present: true };
}

export function vehicleTitleStatus(vehicle) {
    return vehicleRawString(vehicle, 'title_status') || 'Pending';
}

export function vehiclePurchaseDate(vehicle) {
    return formatVehicleDate(vehicle?.raw_data?.purchase_date);
}

export function vehicleArrivedDate(vehicle) {
    return formatVehicleDate(vehicle?.raw_data?.arrived_terminal_date);
}

export function vehicleIsAssigned(vehicle) {
    return vehicle?.status === 'assigned' || !! vehicle?.active_assignment;
}

export function vehicleAssignmentBadgeClass(vehicle) {
    return vehicleIsAssigned(vehicle) ? 'assignment-pill--assigned' : 'assignment-pill--unassigned';
}

const VEHICLE_SOURCE_LABELS = {
    vinstack: 'المستورد',
    manual: 'اليدوي',
    nujoom_al_jazeera: 'نجوم الجزيرة',
};

export function vehicleSourceLabel(source) {
    return VEHICLE_SOURCE_LABELS[source] ?? VEHICLE_SOURCE_LABELS.vinstack;
}

export function vehicleSourcePillClass(source) {
    if (source === 'manual') {
        return 'source-pill--manual';
    }

    if (source === 'nujoom_al_jazeera') {
        return 'source-pill--nujoom';
    }

    return 'source-pill--vinstack';
}

export function vehicleEnteredBy(vehicle) {
    const dealer = vehicle?.active_assignment?.dealer?.company_name;

    if (dealer) {
        return dealer;
    }

    return 'Admin';
}

/** @deprecated */
export function vehicleRouteText(vehicle) {
    const from = vehicleOrigin(vehicle);
    const to = vehicleDestination(vehicle);

    if (from && to) {
        return `${from} → ${to}`;
    }

    return from || to || null;
}

/** @deprecated */
export function vehicleShippingMethod(vehicle) {
    return vehicleRawString(vehicle, 'shipping_method');
}

/** @deprecated */
export function vehicleReferences(vehicle) {
    const items = [];
    const lot = vehicleLot(vehicle);
    const auction = vehicleAuction(vehicle);
    const container = vehicleContainerRef(vehicle);
    const booking = vehicleBookingRef(vehicle);
    const purchase = vehiclePurchaseDate(vehicle);

    if (lot) items.push({ label: 'Lot', value: lot });
    if (auction) items.push({ label: 'Auction', value: auction });
    if (container) items.push({ label: 'Container', value: container });
    if (booking) items.push({ label: 'Booking', value: booking });
    if (purchase) items.push({ label: 'Purchase', value: purchase });

    return items;
}

export { formatVehicleDate };
