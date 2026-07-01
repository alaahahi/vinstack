import { formatVehicleDate } from './vehicleMeta';
import { scalarString } from './scalarString';

export function formatContainerDate(value) {
    return formatVehicleDate(value);
}

export function containerOrigin(container) {
    return scalarString(container?.loading_point);
}

export function containerDestination(container) {
    return scalarString(container?.destination);
}

export function containerRouteText(container) {
    const from = containerOrigin(container);
    const to = containerDestination(container);

    if (from && to) {
        return `${from} → ${to}`;
    }

    return from || to || null;
}

export function containerLineText(container) {
    const line = scalarString(container?.shipping_line);
    const size = scalarString(container?.size);

    if (line && size) {
        return `${line} · ${size}`;
    }

    return line || size || null;
}

export function containerRefs(container) {
    return {
        container: scalarString(container?.container_number),
        booking: scalarString(container?.booking_number),
        seal: scalarString(container?.seal_number),
    };
}

export function containerRowKey(container, index = 0) {
    return container?.id
        ?? container?.container_number
        ?? container?.booking_number
        ?? `container-${index}`;
}

/** @returns {'released'|'arrived'|'loading'|'in_transit'} */
export function containerListStatusKey(container) {
    if (container?.released) {
        return 'released';
    }

    const raw = (scalarString(container?.status) || '').toLowerCase();

    if (raw === 'released' || raw === 'delivered') {
        return 'released';
    }

    if (raw === 'arrived') {
        return 'arrived';
    }

    if (raw === 'loading') {
        return 'loading';
    }

    if (raw === 'pending' || raw === 'in_transit' || raw === '' || !raw) {
        return 'in_transit';
    }

    return 'in_transit';
}

export function containerEtaRaw(container) {
    return scalarString(container?.eta ?? container?.estimated_arrival);
}

export function containerListStatusLabel(container, t = null) {
    const key = containerListStatusKey(container);
    const labels = {
        released: t?.('containers.status.released') ?? 'Released',
        arrived: t?.('containers.status.arrived') ?? 'Arrived',
        loading: t?.('containers.status.loading') ?? 'Loading',
        in_transit: t?.('containers.status.inTransit') ?? 'In transit',
    };

    return labels[key] ?? labels.in_transit;
}

export function containerListStatusClass(container) {
    const key = containerListStatusKey(container);

    if (key === 'released') {
        return 'status-new';
    }

    if (key === 'arrived') {
        return 'status-terminal';
    }

    if (key === 'loading') {
        return 'status-default';
    }

    return 'status-transit';
}

export function containerListStatusEta(container) {
    if (containerListStatusKey(container) !== 'in_transit') {
        return null;
    }

    return formatContainerDate(containerEtaRaw(container));
}
