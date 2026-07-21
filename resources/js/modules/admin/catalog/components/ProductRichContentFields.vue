<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- The shared Inertia form is intentionally edited by this field group. */
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

const props = defineProps(['form', 'activeSection', 'currency']);

const addGallery = () => props.form.gallery.push({ url: '', alt_text: '' });
const addFeatureGroup = () =>
    props.form.feature_groups.push({
        title: '',
        description: '',
        features: '',
    });
const addRequirement = () =>
    props.form.requirements.push({ label: '', value: '' });
const addRelease = () =>
    props.form.changelog.push({ version: '', released_at: '', notes: '' });
const addAddon = () =>
    props.form.addons.push({
        name: '',
        description: '',
        price: '',
        sale_price: '',
        currency: props.currency,
        purchase_url: '',
    });
const addReview = () =>
    props.form.reviews.push({
        name: '',
        title: '',
        rating: 5,
        content: '',
        reviewed_at: '',
    });
</script>

<template>
    <Card v-show="activeSection === 'media'">
        <CardHeader>
            <CardTitle>Media gallery</CardTitle>
            <CardDescription>
                Add product screenshots in the order they should appear on the
                storefront.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(image, index) in form.gallery"
                :key="index"
                class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_1fr_auto]"
            >
                <div class="space-y-2">
                    <Label>Image URL</Label>
                    <Input v-model="image.url" type="url" required />
                    <InputError
                        :message="form.errors[`gallery.${index}.url`]"
                    />
                </div>
                <div class="space-y-2">
                    <Label>Alternative text</Label>
                    <Input v-model="image.alt_text" />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="self-end text-destructive"
                    @click="form.gallery.splice(index, 1)"
                >
                    <Trash2 class="size-4" />
                    <span class="sr-only">Remove image</span>
                </Button>
            </div>
            <Button type="button" variant="outline" @click="addGallery">
                <Plus class="size-4" /> Add screenshot
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'features'">
        <CardHeader>
            <CardTitle>Feature groups</CardTitle>
            <CardDescription>
                Organize long product capabilities into clear storefront
                sections. Enter one feature per line.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(group, index) in form.feature_groups"
                :key="index"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="flex items-start gap-3">
                    <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Group title</Label>
                            <Input v-model="group.title" required />
                            <InputError
                                :message="
                                    form.errors[`feature_groups.${index}.title`]
                                "
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>Short introduction</Label>
                            <Input v-model="group.description" />
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="text-destructive"
                        @click="form.feature_groups.splice(index, 1)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
                <div class="space-y-2">
                    <Label>Features</Label>
                    <textarea
                        v-model="group.features"
                        rows="8"
                        required
                        placeholder="Automated provisioning&#10;Detailed activity logs&#10;Multi-language support"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError
                        :message="
                            form.errors[`feature_groups.${index}.features`]
                        "
                    />
                </div>
            </div>
            <Button type="button" variant="outline" @click="addFeatureGroup">
                <Plus class="size-4" /> Add feature group
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'requirements'">
        <CardHeader>
            <CardTitle>Requirements and compatibility</CardTitle>
            <CardDescription>
                List technical requirements such as WHMCS, PHP, ionCube, themes,
                or supported services.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(requirement, index) in form.requirements"
                :key="index"
                class="grid gap-3 rounded-lg border p-4 md:grid-cols-[220px_1fr_auto]"
            >
                <div class="space-y-2">
                    <Label>Label</Label>
                    <Input
                        v-model="requirement.label"
                        placeholder="WHMCS"
                        required
                    />
                </div>
                <div class="space-y-2">
                    <Label>Supported version or value</Label>
                    <Input
                        v-model="requirement.value"
                        placeholder="9.x back to 8.10"
                        required
                    />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="self-end text-destructive"
                    @click="form.requirements.splice(index, 1)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
            <Button type="button" variant="outline" @click="addRequirement">
                <Plus class="size-4" /> Add requirement
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'changelog'">
        <CardHeader>
            <CardTitle>Changelog</CardTitle>
            <CardDescription>
                Publish version history and release notes. Enter one change per
                line.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(release, index) in form.changelog"
                :key="index"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                    <div class="space-y-2">
                        <Label>Version</Label>
                        <Input
                            v-model="release.version"
                            placeholder="1.0.0"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Release date</Label>
                        <Input v-model="release.released_at" type="date" />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="self-end text-destructive"
                        @click="form.changelog.splice(index, 1)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
                <div class="space-y-2">
                    <Label>Release notes</Label>
                    <textarea
                        v-model="release.notes"
                        rows="6"
                        required
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                </div>
            </div>
            <Button type="button" variant="outline" @click="addRelease">
                <Plus class="size-4" /> Add release
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'addons'">
        <CardHeader>
            <CardTitle>Optional services</CardTitle>
            <CardDescription>
                Offer installation, integration, configuration, or other
                product-specific services.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(addon, index) in form.addons"
                :key="index"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="flex items-start gap-3">
                    <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Service name</Label>
                            <Input v-model="addon.name" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Purchase URL</Label>
                            <Input v-model="addon.purchase_url" type="url" />
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="text-destructive"
                        @click="form.addons.splice(index, 1)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
                <div class="space-y-2">
                    <Label>Description</Label>
                    <textarea
                        v-model="addon.description"
                        rows="3"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label>Currency</Label>
                        <Input
                            v-model="addon.currency"
                            maxlength="3"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Regular price</Label>
                        <Input
                            v-model="addon.price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Sale price</Label>
                        <Input
                            v-model="addon.sale_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                    </div>
                </div>
            </div>
            <Button type="button" variant="outline" @click="addAddon">
                <Plus class="size-4" /> Add optional service
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'reviews'">
        <CardHeader>
            <CardTitle>Customer reviews</CardTitle>
            <CardDescription>
                Publish approved testimonials and ratings for this product.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                v-for="(review, index) in form.reviews"
                :key="index"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="grid gap-4 md:grid-cols-[1fr_1fr_120px_160px_auto]">
                    <div class="space-y-2">
                        <Label>Customer name</Label>
                        <Input v-model="review.name" required />
                    </div>
                    <div class="space-y-2">
                        <Label>Review title</Label>
                        <Input v-model="review.title" />
                    </div>
                    <div class="space-y-2">
                        <Label>Rating</Label>
                        <Input
                            v-model.number="review.rating"
                            type="number"
                            min="1"
                            max="5"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label>Date</Label>
                        <Input v-model="review.reviewed_at" type="date" />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="self-end text-destructive"
                        @click="form.reviews.splice(index, 1)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
                <div class="space-y-2">
                    <Label>Review</Label>
                    <textarea
                        v-model="review.content"
                        rows="4"
                        required
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                </div>
            </div>
            <Button type="button" variant="outline" @click="addReview">
                <Plus class="size-4" /> Add review
            </Button>
        </CardContent>
    </Card>

    <Card v-show="activeSection === 'documentation'">
        <CardHeader>
            <CardTitle>Documentation</CardTitle>
            <CardDescription>
                Provide installation steps, usage notes, and other product
                documentation shown on the storefront.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-7">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <Label>Documentation page title</Label>
                    <Input
                        v-model="form.documentation_title"
                        placeholder="WHMCS Automation Toolkit Documentation"
                    />
                    <InputError :message="form.errors.documentation_title" />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label>Documentation content</Label>
                    <textarea
                        v-model="form.documentation_content"
                        rows="18"
                        placeholder="Installation&#10;&#10;1. Download the package..."
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.documentation_content" />
                </div>
            </div>

            <div class="border-t pt-6">
                <div class="mb-5">
                    <h3 class="font-semibold">Documentation SEO</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        These settings apply to the dedicated public
                        documentation page. Blank values fall back to the
                        product name and description.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <Label>Meta title</Label>
                        <Input
                            v-model="form.documentation_meta_title"
                            placeholder="Product Documentation | ASRTech"
                        />
                        <InputError
                            :message="form.errors.documentation_meta_title"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label>Meta description</Label>
                        <textarea
                            v-model="form.documentation_meta_description"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <InputError
                            :message="
                                form.errors.documentation_meta_description
                            "
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label>Keywords</Label>
                        <Input
                            v-model="form.documentation_keywords"
                            placeholder="installation, configuration, WHMCS"
                        />
                        <InputError
                            :message="form.errors.documentation_keywords"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Robots</Label>
                        <select
                            v-model="form.documentation_robots"
                            class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                        >
                            <option value="index,follow">Index, follow</option>
                            <option value="noindex,follow">
                                No index, follow
                            </option>
                            <option value="noindex,nofollow">
                                No index, no follow
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.documentation_robots"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Social sharing image URL</Label>
                        <Input
                            v-model="form.documentation_open_graph_image"
                            type="url"
                            placeholder="https://..."
                        />
                        <InputError
                            :message="
                                form.errors.documentation_open_graph_image
                            "
                        />
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
