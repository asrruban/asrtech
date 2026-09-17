<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CompatibilityRange = {
    platform: string;
    minimum_version: string;
    maximum_version: string;
    published: boolean;
};
const ranges = defineModel<CompatibilityRange[]>({ required: true });
defineProps<{ errors: Record<string, string> }>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Verified compatibility</CardTitle>
            <CardDescription>
                Publish only ranges you have verified for this product. These
                power the catalog filters. Existing compatibility text does not
                publish a verified range.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
            <p
                id="compatibility-guidance"
                class="rounded-lg bg-muted p-4 text-sm leading-6 text-muted-foreground"
            >
                Enter stable versions as major.minor.patch, for example 8.2.1.
                Both ends are included. A two-part version such as 8.2 means
                exactly 8.2.0. Use the same minimum and maximum for one version,
                or add separate ranges for gaps. Wildcards and prerelease
                versions are not supported.
            </p>
            <InputError :message="errors.compatibility_ranges" />
            <p v-if="!ranges.length" class="text-sm text-muted-foreground">
                No verified compatibility ranges yet. Buyers can still browse
                this product without version filters.
            </p>
            <fieldset
                v-for="(range, index) in ranges"
                :key="index"
                class="space-y-4 rounded-xl border p-4"
                aria-describedby="compatibility-guidance"
            >
                <legend class="px-2 text-sm font-semibold">
                    Compatibility range {{ index + 1 }}
                </legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label :for="`compatibility-${index}-platform`"
                            >Platform</Label
                        >
                        <select
                            :id="`compatibility-${index}-platform`"
                            v-model="range.platform"
                            class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                            :aria-invalid="
                                !!errors[
                                    `compatibility_ranges.${index}.platform`
                                ]
                            "
                            :aria-describedby="`compatibility-${index}-platform-error`"
                        >
                            <option value="whmcs">WHMCS</option>
                            <option value="wordpress">WordPress</option>
                            <option value="php">PHP</option>
                        </select>
                        <InputError
                            :id="`compatibility-${index}-platform-error`"
                            :message="
                                errors[`compatibility_ranges.${index}.platform`]
                            "
                        />
                    </div>
                    <div class="space-y-2">
                        <Label :for="`compatibility-${index}-minimum`"
                            >Minimum version</Label
                        >
                        <Input
                            :id="`compatibility-${index}-minimum`"
                            v-model="range.minimum_version"
                            placeholder="8.2.0"
                            maxlength="11"
                            :aria-invalid="
                                !!errors[
                                    `compatibility_ranges.${index}.minimum_version`
                                ]
                            "
                            :aria-describedby="`compatibility-${index}-minimum-error`"
                        />
                        <InputError
                            :id="`compatibility-${index}-minimum-error`"
                            :message="
                                errors[
                                    `compatibility_ranges.${index}.minimum_version`
                                ]
                            "
                        />
                    </div>
                    <div class="space-y-2">
                        <Label :for="`compatibility-${index}-maximum`"
                            >Maximum version</Label
                        >
                        <Input
                            :id="`compatibility-${index}-maximum`"
                            v-model="range.maximum_version"
                            placeholder="8.2.1"
                            maxlength="11"
                            :aria-invalid="
                                !!errors[
                                    `compatibility_ranges.${index}.maximum_version`
                                ]
                            "
                            :aria-describedby="`compatibility-${index}-maximum-error`"
                        />
                        <InputError
                            :id="`compatibility-${index}-maximum-error`"
                            :message="
                                errors[
                                    `compatibility_ranges.${index}.maximum_version`
                                ]
                            "
                        />
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            v-model="range.published"
                            type="checkbox"
                            class="mt-0.5 size-4 accent-primary"
                        />
                        I have verified this range. Publish it for buyers.
                    </label>
                    <Button
                        type="button"
                        variant="ghost"
                        :aria-label="`Remove compatibility range ${index + 1}`"
                        @click="ranges.splice(index, 1)"
                        ><Trash2 class="size-4" /> Remove</Button
                    >
                </div>
                <InputError
                    :message="errors[`compatibility_ranges.${index}.published`]"
                />
            </fieldset>
            <Button
                type="button"
                variant="outline"
                :disabled="ranges.length >= 30"
                @click="
                    ranges.push({
                        platform: 'whmcs',
                        minimum_version: '',
                        maximum_version: '',
                        published: false,
                    })
                "
                ><Plus class="size-4" /> Add compatibility range</Button
            >
        </CardContent>
    </Card>
</template>
