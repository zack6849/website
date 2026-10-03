<template>
    <article
        class="group flex flex-col rounded border transition"
        :class="[cardClasses, {hilight: project.glow === true}]"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <h5 class="wrap-break-word flex min-w-0 items-center gap-2 font-medium transition-colors" :class="titleClasses">
                <span
                    v-if="isStackedProjectIcon"
                    class="fa-stack shrink-0 text-sm"
                    :class="iconToneClasses"
                    aria-hidden="true"
                >
                    <i
                        v-for="(icon, index) in projectIcons"
                        :key="`${project.name}-icon-${index}`"
                        :class="stackIconClasses(icon, index)"
                    ></i>
                </span>
                <i
                    v-else-if="hasProjectIcon"
                    :class="[projectIcons[0], iconToneClasses]"
                    class="shrink-0 text-sm"
                    aria-hidden="true"
                ></i>
                <span class="min-w-0">{{ project.name }}</span>
            </h5>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                <span v-if="project.status" class="self-start rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadgeClasses(project.status)">
                    {{ project.status }}
                </span>
            </div>
        </div>
        <p v-if="project.note" class="mt-1 text-xs text-gray-400">{{ project.note }}</p>

        <p class="mt-3 text-sm leading-relaxed transition-colors" :class="descriptionClasses">{{ project.description }}</p>

        <div v-if="project.tech?.length" class="mt-3 flex flex-wrap gap-1.5">
            <span
                v-for="tag in visibleTech"
                :key="tag"
                class="rounded border px-2 py-0.5 text-xs transition-colors"
                :class="tagClasses"
            >
                {{ tag }}
            </span>
        </div>

        <div v-if="project.links?.length" class="mt-4 flex flex-wrap gap-x-4 gap-y-1">
            <a
                v-for="link in project.links"
                :key="`${project.name}-${link.url}`"
                :href="link.url"
                :target="isExternal(link.url) ? '_blank' : null"
                :rel="isExternal(link.url) ? 'noopener noreferrer' : null"
                class="text-sm text-brand-700 hover:text-brand-900"
            >
                <i v-if="link.icon" :class="link.icon" class="mr-1" aria-hidden="true"></i>{{ link.label || 'View' }} &rarr;
            </a>
        </div>
    </article>
</template>
<script>
export default {
    name: 'ProjectCardComponent',
    props: {
        project: {
            type: Object,
            required: true,
        },
    },
    computed: {
        isArchived() {
            return this.project.status === 'Archived';
        },
        cardClasses() {
            if (this.isArchived) {
                return 'border-slate-100 bg-slate-50/50 p-4 opacity-70 hover:border-slate-300 hover:bg-white hover:opacity-100 md:p-5';
            }

            return [
                'border-slate-200 bg-white p-5 md:p-6',
                this.project.links?.length ? 'hover:border-brand-300 hover:shadow-sm' : '',
            ];
        },
        titleClasses() {
            return this.isArchived ? 'text-lg text-slate-500 group-hover:text-slate-800' : 'text-xl text-gray-900';
        },
        descriptionClasses() {
            return this.isArchived ? 'text-slate-400 group-hover:text-gray-600' : 'text-gray-600';
        },
        tagClasses() {
            return this.isArchived
                ? 'border-slate-100 bg-white/70 text-slate-400 group-hover:border-slate-200 group-hover:bg-slate-50 group-hover:text-slate-500'
                : 'border-slate-200 bg-slate-50 text-slate-500';
        },
        visibleTech() {
            return (this.project.tech ?? []).slice(0, 5);
        },
        rawProjectIcon() {
            return this.project.icon ?? this.project.icons ?? null;
        },
        projectIcons() {
            const icons = Array.isArray(this.rawProjectIcon)
                ? this.rawProjectIcon
                : [this.rawProjectIcon];

            return icons.filter((icon) => typeof icon === 'string' && icon.trim().length > 0);
        },
        hasProjectIcon() {
            return this.projectIcons.length > 0;
        },
        isStackedProjectIcon() {
            return Array.isArray(this.rawProjectIcon) && this.hasProjectIcon;
        },
        iconToneClasses() {
            const classesByTone = {
                go: 'text-sky-400',
                radio: 'text-emerald-600',
                prize: 'text-purple-600',
                infrastructure: 'text-sky-600',
                marketplace: 'text-indigo-600',
                monitoring: 'text-amber-500',
                reporting: 'text-blue-600',
                modernization: 'text-slate-500',
                commerce: 'text-rose-600',
            };

            return classesByTone[this.project.tone] ?? (this.isArchived ? 'text-slate-400' : 'text-brand-600');
        },
    },
    methods: {
        isExternal(url) {
            return /^https?:\/\//.test(url);
        },
        stackIconClasses(icon, index) {
            if (/\bfa-stack-\dx\b/.test(icon)) {
                return icon;
            }

            return [
                icon,
                index === 0 ? 'fa-stack-2x' : 'fa-stack-1x',
            ];
        },
        statusBadgeClasses(status) {
            const classesByStatus = {
                Live: 'bg-green-100 text-green-700',
                Active: 'bg-teal-100 text-teal-700',
                Private: 'bg-gray-100 text-gray-500',
                Retired: 'bg-gray-100 text-gray-500',
                Archived: 'bg-white text-slate-400 ring-1 ring-slate-200 group-hover:text-slate-500',
            };
            return classesByStatus[status] ?? 'bg-gray-100 text-gray-500';
        },
    },
}
</script>
