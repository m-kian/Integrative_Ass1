import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Enable credentials and XSRF token for Sanctum authentication
 */
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
