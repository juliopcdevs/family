import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  // Auth por token Bearer (stateless): no enviamos cookies, evitando la ruta
  // stateful de Sanctum y su CSRF (que causaba 419 intermitentes en POST).
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
