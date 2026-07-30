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
    return vehicle?.raw_data?.fuel_type?.trim() || null;
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
    return vehicle?.raw_data?.lot?.trim() || null;
}

export function vehicleAuction(vehicle) {
    return vehicle?.raw_data?.auction?.trim() || null;
}

export function vehicleOrigin(vehicle) {
    return vehicle?.raw_data?.loading_point?.trim() || null;
}

export function vehicleDestination(vehicle) {
    return vehicle?.raw_data?.destination?.trim() || null;
}

export function vehicleVinstackStatus(vehicle) {
    return vehicle?.raw_data?.status?.trim() || null;
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
    return vehicle?.raw_data?.container_number?.trim() || null;
}

/** Container payload for ContainerTrackingDialog from a vehicle list row. */
export function vehicleContainerForTracking(vehicle) {
    const raw = vehicle?.raw_data ?? {};
    const containerNumber = raw.container_number?.trim();

    if (! containerNumber) {
        return null;
    }

    return {
        container_number: containerNumber,
        booking_number: raw.booking_number?.trim() || null,
        loading_point: raw.loading_point?.trim() || null,
        destination: raw.destination?.trim() || null,
    };
}

export function vehicleBookingRef(vehicle) {
    return vehicle?.raw_data?.booking_number?.trim() || null;
}

const KEYS_ABSENT = new Set(['no keys', 'missing', 'no key', 'no', 'false']);

export function vehicleKeysInfo(vehicle, t = null) {
    const raw = vehicle?.raw_data?.keys;

    if (raw === null || raw === undefined) {
        return { label: null, present: null };
    }

    if (raw === false) {
        return { label: t?.('vehicleMeta.keysAbsent') ?? 'No Keys', present: false };
    }

    if (raw === true) {
        return { label: t?.('vehicleMeta.keysPresent') ?? 'Present', present: true };
    }

    const keys = String(raw).trim();

    if (! keys) {
        return { label: null, present: null };
    }

    const lower = keys.toLowerCase();

    if (lower === 'present') {
        return { label: t?.('vehicleMeta.keysPresent') ?? 'Present', present: true };
    }

    if (KEYS_ABSENT.has(lower)) {
        return { label: t?.('vehicleMeta.keysAbsent') ?? 'No Keys', present: false };
    }

    return { label: keys, present: true };
}

export function vehicleTitleStatus(vehicle, t = null) {
    const status = vehicle?.raw_data?.title_status?.trim();

    return status || (t?.('vehicleMeta.titlePending') ?? 'Pending');
}

export function vehiclePurchaseDate(vehicle) {
    return formatVehicleDate(vehicle?.raw_data?.purchase_date);
}

export function vehicleEtaDate(vehicle) {
    return formatVehicleDate(vehicle?.eta || vehicle?.raw_data?.eta);
}

export function vehicleArrivedDate(vehicle) {
    return formatVehicleDate(vehicle?.raw_data?.arrived_terminal_date);
}

const SOURCE_KEYS = {
    manual: 'vehicles.source.manual',
    vinstack: 'vehicles.source.vinstack',
    nujoom_al_jazeera: 'vehicles.source.nujoom',
};

const SOURCE_FALLBACK = {
    manual: 'Manual',
    vinstack: 'Imported',
    nujoom_al_jazeera: 'Nujoom Al Jazeera',
};

export function vehicleSourceLabel(vehicle, t = null) {
    if (vehicle?.source_label) {
        return vehicle.source_label;
    }

    const source = vehicle?.source ?? 'vinstack';
    const key = SOURCE_KEYS[source];

    if (t && key) {
        return t(key);
    }

    return SOURCE_FALLBACK[source] ?? SOURCE_FALLBACK.vinstack;
}

const SOURCE_PILL_CLASSES = {
    manual: 'source-pill--manual',
    vinstack: 'source-pill--vinstack',
    nujoom_al_jazeera: 'source-pill--nujoom',
};

export function vehicleSourcePillClass(vehicle) {
    const source = vehicle?.source ?? 'vinstack';

    return SOURCE_PILL_CLASSES[source] ?? SOURCE_PILL_CLASSES.vinstack;
}

export function vehicleIsAssigned(vehicle) {
    return vehicle?.status === 'assigned' || !! vehicle?.active_assignment;
}

export function vehicleAssignmentBadgeClass(vehicle) {
    return vehicleIsAssigned(vehicle) ? 'assignment-pill--assigned' : 'assignment-pill--unassigned';
}

export function vehicleEnteredBy(vehicle, t = null) {
    const dealer = vehicle?.active_assignment?.dealer?.user?.name
        || vehicle?.active_assignment?.dealer?.company_name;

    if (dealer) {
        return dealer;
    }

    if (vehicle?.source === 'nujoom_al_jazeera') {
        return vehicleSourceLabel(vehicle, t);
    }

    return t?.('vehicleMeta.enteredByAdmin') ?? 'Admin';
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
    return vehicle?.raw_data?.shipping_method?.trim() || null;
}

/** @deprecated */
export function vehicleReferences(vehicle, t = null) {
    const items = [];
    const lot = vehicleLot(vehicle);
    const auction = vehicleAuction(vehicle);
    const container = vehicleContainerRef(vehicle);
    const booking = vehicleBookingRef(vehicle);
    const purchase = vehiclePurchaseDate(vehicle);
    const refs = t
        ? {
            lot: t('vehicleMeta.refs.lot'),
            auction: t('vehicleMeta.refs.auction'),
            container: t('vehicleMeta.refs.container'),
            booking: t('vehicleMeta.refs.booking'),
            purchase: t('vehicleMeta.refs.purchase'),
        }
        : {
            lot: 'Lot',
            auction: 'Auction',
            container: 'Container',
            booking: 'Booking',
            purchase: 'Purchase',
        };

    if (lot) items.push({ label: refs.lot, value: lot });
    if (auction) items.push({ label: refs.auction, value: auction });
    if (container) items.push({ label: refs.container, value: container });
    if (booking) items.push({ label: refs.booking, value: booking });
    if (purchase) items.push({ label: refs.purchase, value: purchase });

    return items;
}

export { formatVehicleDate };
