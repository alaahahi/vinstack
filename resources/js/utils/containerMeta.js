import { formatVehicleDate } from './vehicleMeta';

export function formatContainerDate(value) {
    return formatVehicleDate(value);
}

export function containerRouteText(container) {
    const from = container?.loading_point?.trim();
    const to = container?.destination?.trim();

    if (from && to) {
        return `${from} → ${to}`;
    }

    return from || to || null;
}

export function containerLineText(container) {
    const line = container?.shipping_line?.trim();
    const size = container?.size?.trim();

    if (line && size) {
        return `${line} · ${size}`;
    }

    return line || size || null;
}

export function containerRefs(container) {
    return {
        container: container?.container_number?.trim() || null,
        booking: container?.booking_number?.trim() || null,
        seal: container?.seal_number?.trim() || null,
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

    const raw = (container?.status || '').toLowerCase().trim();

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
    const value = container?.eta ?? container?.estimated_arrival;

    return typeof value === 'string' ? value.trim() || null : value ?? null;
}

export function containerListStatusLabel(container) {
    const key = containerListStatusKey(container);

    if (key === 'released') {
        return 'تم الإفراج';
    }

    if (key === 'arrived') {
        return 'وصل';
    }

    if (key === 'loading') {
        return 'تحميل';
    }

    return 'في الطريق';
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
