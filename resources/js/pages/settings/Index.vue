<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { InquirySettings, LocalizationSettings } from '@/types/ui';
import InquirySettingsPanel from './InquirySettingsPanel.vue';
import LocalizationSettingsPanel from './LocalizationSettingsPanel.vue';
import SettingsTabs from './SettingsTabs.vue';

type SettingsTab = 'localization' | 'inquiries' | 'appearance';

defineProps<{
    localizationSettings: LocalizationSettings;
    inquirySettings: InquirySettings;
}>();

const { t } = useTranslations();
const activeTab = ref<SettingsTab>('localization');

onMounted(() => {
    const storedTab = sessionStorage.getItem('settings.activeTab');

    if (
        storedTab === 'localization' ||
        storedTab === 'inquiries' ||
        storedTab === 'appearance'
    ) {
        activeTab.value = storedTab;
    }
});

watch(activeTab, (tab) => {
    sessionStorage.setItem('settings.activeTab', tab);
});
</script>

<template>
    <Head :title="t('General settings')" />

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
        <header class="shrink-0 px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
            <h1
                class="text-[1.75rem] font-semibold tracking-[-0.04em] lg:text-[2rem]"
            >
                {{ t('General settings') }}
            </h1>
        </header>

        <div
            class="shrink-0 border-y border-black/8 px-4 py-3 sm:px-6 lg:px-8 dark:border-white/10"
        >
            <SettingsTabs v-model="activeTab" />
        </div>

        <div
            class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6 lg:px-8"
            scroll-region
        >
            <LocalizationSettingsPanel
                v-if="activeTab === 'localization'"
                :settings="localizationSettings"
            />

            <InquirySettingsPanel
                v-else-if="activeTab === 'inquiries'"
                :settings="inquirySettings"
            />

            <section
                v-else
                class="max-w-3xl border-y border-black/8 py-5 dark:border-white/10"
            >
                <h2 class="text-base font-semibold">
                    {{ t('Appearance') }}
                </h2>
                <div class="mt-4">
                    <AppearanceTabs />
                </div>
            </section>
        </div>
    </div>
</template>
