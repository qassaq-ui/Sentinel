<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { index as inquiriesIndex } from '@/routes/inquiries';
import { Head, Link } from '@inertiajs/vue3';

const { t } = useTranslations();
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <Head :title="t('New inquiry')" />

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
            <div class="flex shrink-0 items-center justify-between gap-4">
                <h1 class="text-lg font-semibold">{{ t('New inquiry') }}</h1>
                <Button as-child variant="link" class="h-auto px-0 py-0 font-semibold">
                    <Link :href="inquiriesIndex()">
                        {{ t('Inquiries') }}
                    </Link>
                </Button>
            </div>

            <div
                class="flex min-h-0 flex-1 items-center justify-center rounded-lg border border-border bg-background p-6"
            >
                <p class="text-sm font-medium text-muted-foreground">
                    {{ t('Inquiry form will be here') }}
                </p>
            </div>
        </div>
    </div>
</template>
