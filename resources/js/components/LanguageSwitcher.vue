<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Globe2 } from '@lucide/vue';
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import { update } from '@/routes/locale';

const page = usePage();
const localization = computed(() => page.props.locale);
const { t } = useTranslations();
const localizedLocales = computed(() =>
    localization.value.available.map((locale) => ({
        ...locale,
        label: t(`language.${locale.code}`) || locale.label,
    })),
);
const currentLocaleLabel = computed(
    () =>
        localizedLocales.value.find(
            (locale) => locale.code === localization.value.current,
        )?.label ?? localization.value.current,
);

function updateLocale(locale: string) {
    if (locale === localization.value.current) {
        return;
    }

    router.post(
        update.url(),
        { locale },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
}
</script>

<template>
    <div class="flex items-center">
        <Select
            :model-value="localization.current"
            @update:model-value="updateLocale(String($event))"
        >
            <SelectTrigger
                size="sm"
                class="group h-10 min-w-24 gap-2 rounded-full border-0 bg-white/55 py-1 pr-3 pl-1.5 text-slate-800 shadow-[0_4px_18px_rgba(15,23,42,0.06)] ring-1 ring-white/70 backdrop-blur-md transition-all hover:bg-white/70 hover:shadow-[0_6px_22px_rgba(15,23,42,0.08)] focus:ring-2 focus:ring-[#007aff]/25 data-[state=open]:bg-white/75 data-[state=open]:shadow-[0_8px_26px_rgba(15,23,42,0.09)] dark:bg-white/8 dark:text-white dark:ring-white/12 dark:hover:bg-white/12 [&>svg:last-child]:hidden"
                :aria-label="t('Select language')"
            >
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/65 text-[#1875e6] ring-1 ring-white/80 dark:bg-blue-500/12 dark:text-blue-300 dark:ring-blue-400/15"
                    aria-hidden="true"
                >
                    <Globe2 class="size-4" :stroke-width="1.8" />
                </span>
                <span
                    class="min-w-0 flex-1 truncate text-xs leading-none font-semibold"
                >
                    {{ currentLocaleLabel }}
                </span>
                <ChevronDown
                    class="size-3.5 shrink-0 text-slate-400 transition-transform duration-200 group-data-[state=open]:rotate-180 dark:text-slate-400"
                    :stroke-width="2"
                    aria-hidden="true"
                />
            </SelectTrigger>
            <SelectContent
                align="end"
                :side-offset="8"
                class="min-w-52 rounded-2xl border-0 bg-white/80 p-1.5 text-slate-900 shadow-[0_16px_40px_rgba(15,23,42,0.12)] ring-1 ring-white/75 backdrop-blur-md dark:bg-slate-900/80 dark:text-white dark:ring-white/10"
            >
                <SelectItem
                    v-for="locale in localizedLocales"
                    :key="locale.code"
                    :value="locale.code"
                    class="min-h-11 rounded-xl py-2 pr-9 pl-2.5 font-medium focus:bg-[#f5f5f7] focus:text-slate-950 dark:focus:bg-white/10 dark:focus:text-white"
                >
                    <span
                        class="flex size-7 items-center justify-center rounded-full bg-slate-100 text-[0.625rem] font-bold tracking-[0.08em] text-slate-500 uppercase dark:bg-white/10 dark:text-slate-300"
                        aria-hidden="true"
                    >
                        {{ locale.code.slice(0, 2) }}
                    </span>
                    <span>{{ locale.label }}</span>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
