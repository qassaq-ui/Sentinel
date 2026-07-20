<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { send } from '@/routes/verification';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Profile settings')" />

    <div class="min-h-0 flex-1 overflow-y-auto" scroll-region>
        <header class="px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
            <h1
                class="text-[1.75rem] font-semibold tracking-[-0.04em] lg:text-[2rem]"
            >
                {{ t('Profile settings') }}
            </h1>
        </header>

        <div
            class="border-y border-black/8 px-4 py-6 sm:px-6 lg:px-8 dark:border-white/10"
        >
            <Form
                v-bind="ProfileController.update.form()"
                class="max-w-2xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        :placeholder="t('Full name')"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('Email address') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        :placeholder="t('Email address')"
                    />
                    <InputError class="mt-2" :message="errors.email" />
                </div>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                >
                    <p class="-mt-4 text-sm text-muted-foreground">
                        {{ t('Your email address is unverified.') }}
                        <Link
                            :href="send()"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            {{
                                t(
                                    'Click here to re-send the verification email.',
                                )
                            }}
                        </Link>
                    </p>

                    <div
                        v-if="page.props.status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        {{
                            t(
                                'A new verification link has been sent to your email address.',
                            )
                        }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                        >{{ t('Save') }}</Button
                    >
                </div>
            </Form>

            <div
                class="mt-8 max-w-2xl border-t border-black/8 pt-6 dark:border-white/10"
            >
                <DeleteUser />
            </div>
        </div>
    </div>
</template>
