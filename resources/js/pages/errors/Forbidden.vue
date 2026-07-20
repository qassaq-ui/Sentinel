<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ShieldX } from '@lucide/vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

defineProps<{
    status: number;
}>();

const { t } = useTranslations();

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    router.visit(dashboard());
}
</script>

<template>
    <div
        class="relative flex min-h-svh items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 text-slate-950 dark:bg-slate-950 dark:text-slate-50"
    >
        <Head :title="t('Access restricted')" />

        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <PlaceholderPattern class="opacity-25 dark:opacity-15" />
            <div
                class="absolute -top-48 -left-40 size-[32rem] rounded-full bg-blue-200/55 blur-3xl dark:bg-blue-950/40"
            />
            <div
                class="absolute -right-40 -bottom-56 size-[34rem] rounded-full bg-indigo-200/45 blur-3xl dark:bg-indigo-950/35"
            />

            <div
                class="absolute top-1/2 left-1/2 hidden -translate-x-1/2 -translate-y-1/2 items-center justify-center lg:flex"
            >
                <ShieldX
                    :stroke-width="0.65"
                    class="size-[38rem] text-blue-950/5 dark:text-blue-100/5"
                />
            </div>
        </div>

        <main class="relative z-10 w-full max-w-2xl">
            <div class="relative px-6 py-10 text-center sm:px-12 sm:py-14">
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ t('Access restricted') }}
                </h1>
                <p
                    class="mx-auto mt-4 max-w-md text-sm leading-6 text-slate-600 sm:text-base dark:text-slate-300"
                >
                    {{ t('You do not have permission to view this page.') }}
                </p>

                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-4 sm:flex-row sm:gap-7"
                >
                    <button
                        type="button"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 text-sm font-medium text-slate-600 transition-colors hover:text-slate-950 focus-visible:underline focus-visible:outline-none dark:text-slate-300 dark:hover:text-white"
                        @click="goBack"
                    >
                        <ArrowLeft class="size-4" />
                        {{ t('Return to previous page') }}
                    </button>

                    <Link
                        :href="dashboard()"
                        class="text-sm font-semibold text-blue-700 transition-colors hover:text-blue-900 focus-visible:underline focus-visible:outline-none dark:text-blue-300 dark:hover:text-blue-100"
                    >
                        {{ t('Go to dashboard') }}
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
