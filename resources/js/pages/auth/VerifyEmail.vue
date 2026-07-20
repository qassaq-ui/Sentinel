<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Email verification')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-center text-sm leading-5 font-medium text-emerald-700"
    >
        {{
            t(
                'A new verification link has been sent to the email address you provided during registration.',
            )
        }}
    </div>

    <Form
        v-bind="send.form()"
        disable-while-processing
        class="grid gap-5 text-center inert:pointer-events-none inert:opacity-70"
        v-slot="{ processing }"
    >
        <SubmitButton
            :processing="processing"
            class="h-12 rounded-xl bg-[#0071e3] text-base font-semibold text-white shadow-none hover:bg-[#0077ed] active:scale-[0.99]"
        >
            {{ t('Resend verification email') }}
        </SubmitButton>

        <TextLink
            :href="logout()"
            as="button"
            class="mx-auto block text-sm font-medium text-slate-500 no-underline hover:text-[#0071e3] hover:underline"
        >
            {{ t('Log out') }}
        </TextLink>
    </Form>
</template>
