<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
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
                class="h-8 min-w-16 gap-1.5 border-sidebar-border/70 px-2 [&>svg:last-child]:hidden"
                :aria-label="t('Select language')"
            >
                <Languages class="size-4 text-muted-foreground" />
                <span class="text-xs font-medium leading-none">
                    {{ currentLocaleLabel }}
                </span>
            </SelectTrigger>
            <SelectContent align="end">
                <SelectItem
                    v-for="locale in localizedLocales"
                    :key="locale.code"
                    :value="locale.code"
                >
                    {{ locale.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
