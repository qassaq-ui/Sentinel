<script setup lang="ts">
type Props = {
    compact?: boolean;
};

withDefaults(defineProps<Props>(), {
    compact: false,
});
</script>

<template>
    <div
        class="relative inline-flex items-center"
        :class="compact ? 'h-14 pl-5' : 'h-12 pl-5 sm:h-16 sm:pl-6'"
        aria-label="Speak Up"
    >
        <span
            class="absolute left-0 rounded-full border-[#1875e6]"
            :class="
                compact
                    ? 'size-12 border-[7px]'
                    : 'size-10 border-[6px] sm:size-14 sm:border-8'
            "
        />
        <span
            class="absolute bg-white"
            :class="
                compact
                    ? 'left-7 h-5 w-5'
                    : 'left-6 h-4 w-4 sm:left-8 sm:h-6 sm:w-6'
            "
        />
        <span
            class="relative font-bold tracking-[-0.05em] text-[#171717]"
            :class="compact ? 'text-2xl' : 'text-xl sm:text-2xl'"
        >
            Speak <span class="text-[#1875e6]">Up</span>
        </span>
    </div>
</template>
