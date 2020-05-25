import axios from "axios";

if (window.Craft === undefined) {
    console.warn('Unable to find Craft, oh dear!');
}

let Axios = axios;
Axios.defaults.headers.common['Content-Type'] = 'application/json';
Axios.defaults.headers.common['Accept'] = 'application/json';

export default {
    getSites: (token = null) => {
        if (token !== null) {
            return Axios.get('/ohdear/api/sites', {params: {'api-token': token}});
        }
        return Axios.get('/ohdear/api/sites');
    },
    getSite: () => {
        return Axios.get('/ohdear/api/site');
    },
    getUptime: (startedAt, endedAt, split) => {
        return Axios.get(`/ohdear/api/uptime?filter[started_at]=${startedAt}&filter[ended_at]=${endedAt}&split=${split}`);
    },
    getDowntime: (startedAt, endedAt) => {
        return Axios.get(`/ohdear/api/downtime?filter[started_at]=${startedAt}&filter[ended_at]=${endedAt}`);
    },
    getBrokenLinks: () => {
        return Axios.get('/ohdear/api/broken-links');
    },
    getMixedContent: () => {
        return Axios.get('/ohdear/api/mixed-content');
    },
    getCertificateHealth: () => {
        return Axios.get('/ohdear/api/certificate-health');
    },
    disableCheck: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/disable-check', data).then(response => response.data);
    },
    enableCheck: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/enable-check', data).then(response => response.data);
    },
    requestRun: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/request-run', data).then(response => response.data);
    },
};
