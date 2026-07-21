<script setup lang="ts">
import type { TabsIndicatorProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsIndicator } from "reka-ui"
import { nextTick, onMounted, ref } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<TabsIndicatorProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

// reka-ui measures the active trigger before the tabs list has registered,
// so the indicator stays hidden until the first tab change without this kick.
const indicator = ref<{ updateIndicatorStyle?: () => void } | null>(null)

onMounted(async () => {
  await nextTick()
  indicator.value?.updateIndicatorStyle?.()
})
</script>

<template>
  <TabsIndicator
    ref="indicator"
    data-slot="tabs-indicator"
    v-bind="delegatedProps"
    :class="
      cn(
        'absolute bottom-0 left-0 h-0.5 w-[var(--reka-tabs-indicator-size)] translate-x-[var(--reka-tabs-indicator-position)] rounded-full transition-[width,translate] duration-300',
        props.class,
      )"
  >
    <slot />
  </TabsIndicator>
</template>
