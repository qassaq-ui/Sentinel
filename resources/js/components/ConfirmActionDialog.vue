<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    title: string;
    description: string;
    confirmLabel: string;
    cancelLabel?: string;
    confirmVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
};

withDefaults(defineProps<Props>(), {
    cancelLabel: 'Cancel',
    confirmVariant: 'destructive',
});

const emit = defineEmits<{
    confirm: [];
}>();

const open = ref(false);

function confirmAction() {
    open.value = false;
    emit('confirm');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>

        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">
                        {{ cancelLabel }}
                    </Button>
                </DialogClose>

                <Button :variant="confirmVariant" @click="confirmAction">
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
