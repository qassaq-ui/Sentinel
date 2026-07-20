<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';
import SpeakUpLogo from '@/pages/public/Inquiries/SpeakUpLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-[#f8f9fb] px-4 py-8 font-[ui-sans-serif,-apple-system,BlinkMacSystemFont,'SF_Pro_Display','Segoe_UI',sans-serif] text-slate-950 sm:px-6"
    >
        <img
            src="/images/auth/speakup-login-background.webp"
            alt=""
            class="pointer-events-none absolute inset-0 size-full object-cover object-center"
            aria-hidden="true"
        />
        <div
            class="relative w-full max-w-[27rem] rounded-3xl bg-white px-5 py-7 shadow-[0_12px_35px_rgba(27,42,60,0.08)] ring-1 ring-[#e3e5e8] sm:px-9 sm:py-9"
        >
            <div class="flex flex-col gap-7">
                <div class="flex flex-col items-center gap-3">
                    <Link
                        :href="home()"
                        class="rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0071e3]"
                    >
                        <SpeakUpLogo compact />
                        <span class="sr-only">{{ t('Log in') }}</span>
                    </Link>
                    <div
                        v-if="title || description"
                        class="space-y-1.5 text-center"
                    >
                        <h1
                            v-if="title"
                            class="text-[1.375rem] leading-7 font-semibold tracking-[-0.025em]"
                        >
                            {{ title ? t(title) : '' }}
                        </h1>
                        <p
                            v-if="description"
                            class="mx-auto max-w-xs text-center text-sm leading-5 text-slate-500"
                        >
                            {{ description ? t(description) : '' }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
