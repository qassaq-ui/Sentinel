<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type Props = {
    passwordRules: string;
};

const props = defineProps<Props>();
const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Security settings')" />

    <div class="min-h-0 flex-1 overflow-y-auto" scroll-region>
        <header class="px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
            <h1
                class="text-[1.75rem] font-semibold tracking-[-0.04em] lg:text-[2rem]"
            >
                {{ t('Security settings') }}
            </h1>
        </header>

        <div
            class="border-y border-black/8 px-4 py-6 sm:px-6 lg:px-8 dark:border-white/10"
        >
            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="max-w-2xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="current_password">{{
                        t('Current password')
                    }}</Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        :placeholder="t('Current password')"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('New password') }}</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :placeholder="t('New password')"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{
                        t('Confirm password')
                    }}</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :placeholder="t('Confirm password')"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-password-button"
                    >
                        {{ t('Save') }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
