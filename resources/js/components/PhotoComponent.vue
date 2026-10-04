<!-- resources/js/components/PhotoComponent.vue -->
<template>
    <article class="photo-container overflow-hidden rounded bg-white shadow-lg">
        <div>
            <img
                @click="openFlickr"
                class="h-auto max-h-112 w-full cursor-pointer object-cover object-center"
                :src="info.url_m"
                :alt="info.title"
                loading="lazy"
                decoding="async"
            >
            <div class="px-4 py-3">
                <div class="mb-2 text-lg font-bold">{{ info.title }}</div>
            </div>
            <div class="px-4 pb-4">
                <span
                    v-if="info.tags.length > 0"
                    v-for="tag in info.tags.split(' ')"
                    :key="tag"
                    v-text="tag"
                    class="inline-block bg-gray-200 rounded-full px-3 py-1 mb-1 text-sm font-semibold text-gray-700"
                />
            </div>
        </div>
    </article>
</template>

<script>
export default {
    name: 'PhotoComponent',
    props: {
        info: {
            type: Object,
            required: true
        }
    },
    methods: {
        openFlickr() {
            const url = `https://www.flickr.com/photos/${this.info.owner}/${this.info.id}`;
            const opened = window.open(url, '_blank', 'noopener,noreferrer');

            if (opened) {
                opened.opener = null;
            }
        }
    }
}
</script>
