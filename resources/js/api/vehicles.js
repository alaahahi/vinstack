import api from './client';



export async function checkVinExists(vin, excludeVehicleId = null) {

    const params = excludeVehicleId ? { exclude: excludeVehicleId } : {};

    const { data } = await api.get(`/admin/vehicles/check-vin/${encodeURIComponent(vin)}`, { params });



    return data.data;

}



export async function decodeVin(vin) {

    const { data } = await api.get(`/admin/vehicles/decode-vin/${encodeURIComponent(vin)}`);



    return data.data;

}



export async function createManualVehicle(payload) {

    const { data } = await api.post('/admin/vehicles', payload);



    return data;

}



export async function updateManualVehicle(vehicleId, payload) {

    const { data } = await api.put(`/admin/vehicles/${vehicleId}`, payload);



    return data;

}



export async function deleteManualVehicle(vehicleId) {

    const { data } = await api.delete(`/admin/vehicles/${vehicleId}`);



    return data;

}



export async function restoreVehicle(vehicleId) {

    const { data } = await api.post(`/admin/vehicles/${vehicleId}/restore`);



    return data;

}



export async function fetchVehicleOptions() {

    const { data } = await api.get('/admin/settings/vehicle-options');



    return data.data;

}


