<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';
import { Button } from '@/components/ui/button';

const { appearance, updateAppearance } = useAppearance();
const { t } = useTranslations();

const nextAppearance = computed(() => {
    if (appearance.value === 'light') {
        return 'dark';
    }

    if (appearance.value === 'dark') {
        return 'system';
    }

    return 'light';
});

const label = computed(() => {
    if (appearance.value === 'light') {
        return t('Switch to dark theme');
    }

    if (appearance.value === 'dark') {
        return t('Switch to system theme');
    }

    return t('Switch to light theme');
});

function toggleTheme(): void {
    updateAppearance(nextAppearance.value);
}
</script>

<template>
    <Button
        variant="outline"
        size="icon"
        class="size-8 border-sidebar-border/70"
        :aria-label="label"
        @click="toggleTheme"
    >
        <Sun v-if="appearance === 'light'" class="size-4 text-muted-foreground" />
        <Moon
            v-else-if="appearance === 'dark'"
            class="size-4 text-muted-foreground"
        />
        <Monitor v-else class="size-4 text-muted-foreground" />
    </Button>
</template>
