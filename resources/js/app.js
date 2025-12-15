import "./bootstrap";

import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./components/App.vue";
import router from "./router";
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(Toast, { position: "top-right", timeout: 5000 });
app.mount("#app");
