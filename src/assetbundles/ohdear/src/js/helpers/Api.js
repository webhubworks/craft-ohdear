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
        return Axios.get('/ohdear/api/sites')
            .catch(error => handleError(error));
    },
    getSite: () => {
        return Axios.get('/ohdear/api/site')
            .catch(error => handleError(error));
    },
    getUptime: (startedAt, endedAt, split) => {
        return Axios.get(`/ohdear/api/uptime?filter[started_at]=${startedAt}&filter[ended_at]=${endedAt}&split=${split}`)
            .catch(error => handleError(error));
    },
    getDowntime: (startedAt, endedAt) => {
        return Axios.get(`/ohdear/api/downtime?filter[started_at]=${startedAt}&filter[ended_at]=${endedAt}`)
            .catch(error => handleError(error));
    },
    getBrokenLinks: () => {
        return Axios.get('/ohdear/api/broken-links')
            .catch(error => handleError(error));
    },
    getMixedContent: () => {
        return Axios.get('/ohdear/api/mixed-content')
            .catch(error => handleError(error));
    },
    getCertificateHealth: () => {
        return Axios.get('/ohdear/api/certificate-health')
            .catch(error => handleError(error));
    },
    getCurrentPerformance: () => {
        return Axios.get('/ohdear/api/current-performance')
            .catch(error => handleError(error));
    },
    getPerformance: (start, end, groupBy = null) => {
        return Axios.get('/ohdear/api/performance', {
            params: {
                start, end, groupBy
            }
        })
            .catch(error => handleError(error));
    },
    disableCheck: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/disable-check', data).then(response => response.data)
            .catch(error => handleError(error));
    },
    enableCheck: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/enable-check', data).then(response => response.data)
            .catch(error => handleError(error));
    },
    requestRun: checkId => {
        let data = {};
        data['checkId'] = checkId;
        data[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;
        return Axios.post('/ohdear/api/request-run', data).then(response => response.data)
            .catch(error => handleError(error));
    },
};

function handleError(error) {
    if (error.response) {
        // The request was made and the server responded with a status code
        // that falls out of the range of 2xx
        if (error.response.data.hasOwnProperty('error')) {
            if (error.response.data.error === '{"error":"Unauthenticated."}') {
                alert('The Oh Dear API key is invalid.');
            }
        }
        console.log(error.response.status);
        console.log(error.response.headers);
    } else if (error.request) {
        // The request was made but no response was received
        // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
        // http.ClientRequest in node.js
        console.log(error.request);
    } else {
        // Something happened in setting up the request that triggered an Error
        console.log('Error', error.message);
    }
    console.log(error.config);
}
