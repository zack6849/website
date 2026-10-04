import { createApp } from "vue/dist/vue.esm-bundler";
import { createIsland } from './support/islands.mjs';
import App from "./components/App.vue";
import projectCardComponent from "./components/ProjectCardComponent.vue";
import technologyComponent from "./components/TechnologyComponent.vue";

// App adopts the Blade markup as its template, so the Vue compiler is required.
const app = createApp(App);
app.component('photo-gallery', createIsland(() => import('./components/PhotoGalleryComponent.vue'), 'Photo gallery'));
app.component('project-card', projectCardComponent);
app.component('technology', technologyComponent);
app.component('qso-map', createIsland(() => import('./components/QSOMapComponent.vue'), 'Radio map'));
app.component('showcase', createIsland(() => import('./components/Showcase.vue'), 'Project showcase'));
app.mount('#app');
