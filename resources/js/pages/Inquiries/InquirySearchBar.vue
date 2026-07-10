<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { Search } from '@lucide/vue';

type Props = {
    modelValue: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    clear: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
        <div class="relative min-w-0">
            <Search
                class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                :model-value="modelValue"
                class="h-11 rounded-lg border-transparent bg-muted pl-11 text-sm shadow-none"
                :placeholder="t('Search by number, subject, status, category')"
                @update:model-value="emit('update:modelValue', String($event))"
            />
        </div>

        <Button
            type="button"
            variant="secondary"
            class="h-11 rounded-lg px-5 font-semibold"
            @click="emit('clear')"
        >
            {{ t('Clear') }}
        </Button>
    </div>
</template>
